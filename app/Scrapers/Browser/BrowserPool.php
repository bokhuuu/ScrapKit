<?php

declare(strict_types=1);

namespace App\Scrapers\Browser;

use Facebook\WebDriver\Chrome\ChromeOptions;
use Facebook\WebDriver\Remote\DesiredCapabilities;
use Facebook\WebDriver\Remote\RemoteWebDriver;
use Illuminate\Support\Facades\Log;
use Laravel\Dusk\Browser;
use RuntimeException;
use Throwable;

/**
 * Manages a fixed pool of reusable Chrome browser instances.
 *
 * Lives as a singleton within a single worker process.
 * Browsers are created once and reused across jobs - no cross-process
 * sharing, no Redis session IDs. Each worker maintains its own pool.
 *
 * Jobs acquire a browser, scrape, then release it back.
 * If all browsers are busy, acquire() blocks until one is free.
 */
class BrowserPool
{
    /** @var Browser[] */
    private array $available = [];

    private bool $initialized = false;

    public function __construct(
        private readonly int $size,
    ) {}

    /**
     * Create all browser instances and hold them in memory.
     *
     * Called automatically on first acquire() if not already initialized.
     * Safe to call explicitly before a scrape run starts.
     */
    public function initialize(): void
    {
        if ($this->initialized) {
            return;
        }

        Log::warning('BrowserPool: initializing fresh pool', [
            'size' => $this->size,
            'pid' => getmypid(),
            'object_id' => spl_object_id($this),
        ]);

        $i = 0;

        try {
            for ($i = 0; $i < $this->size; $i++) {
                Log::info('BrowserPool: creating browser', ['index' => $i]);
                $this->available[] = $this->createBrowser();
                Log::info('BrowserPool: browser created', ['index' => $i]);
            }

            $this->initialized = true;
        } catch (Throwable $e) {
            Log::error('BrowserPool: initialize failed', [
                'index_reached' => $i,
                'error' => $e->getMessage(),
            ]);

            throw $e;
        }
    }

    /**
     * Acquire a browser from the pool.
     *
     * Polls until a browser is available (up to 30 seconds).
     * Auto-initializes the pool on first call.
     */
    public function acquire(): Browser
    {
        $this->initialize();

        $waited = 0;

        while (empty($this->available)) {
            if ($waited >= 30) {
                Log::error('BrowserPool: timeout waiting for browser', [
                    'available' => count($this->available),
                    'pool_size' => $this->size,
                ]);
                throw new RuntimeException('BrowserPool: timed out waiting for available browser.');
            }

            usleep(200_000);
            $waited += 0.2;
        }

        $browser = array_shift($this->available);
        Log::info('BrowserPool: acquired', [
            'remaining' => count($this->available),
            'object_id' => spl_object_id($this),
        ]);

        return $browser;
    }

    /**
     * Return a browser to the pool after use.
     */
    public function release(Browser $browser): void
    {
        $this->available[] = $browser;
        Log::info('BrowserPool: released', ['available' => count($this->available)]);
    }

    /**
     * Shut down all browser instances and reset the pool.
     *
     * Called after a scrape run completes or on worker shutdown.
     */
    public function teardown(): void
    {
        foreach ($this->available as $browser) {
            try {
                $browser->quit();
            } catch (Throwable) {
            }
        }

        $this->available = [];
        $this->initialized = false;
    }

    /**
     * Spin up a single Chrome instance.
     */
    private function createBrowser(): Browser
    {
        $options = new ChromeOptions;
        $options->addArguments([
            '--headless',
            '--no-sandbox',
            '--disable-gpu',
            '--disable-dev-shm-usage',
            '--window-size='.config('scraper.browser_window_size'),
        ]);

        $capabilities = DesiredCapabilities::chrome();
        $capabilities->setCapability(ChromeOptions::CAPABILITY, $options);

        $driver = RemoteWebDriver::create(
            config('scraper.chromedriver_url'),
            $capabilities,
            (int) config('scraper.browser_connect_timeout_ms'),
            (int) config('scraper.browser_request_timeout_ms'),
        );

        return new Browser($driver);
    }
}
