<?php

declare(strict_types=1);

namespace App\Scrapers\Browser;

use Facebook\WebDriver\Chrome\ChromeOptions;
use Facebook\WebDriver\Remote\DesiredCapabilities;
use Facebook\WebDriver\Remote\RemoteWebDriver;
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

        for ($i = 0; $i < $this->size; $i++) {
            $this->available[] = $this->createBrowser();
        }

        $this->initialized = true;
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
                throw new RuntimeException('BrowserPool: timed out waiting for available browser.');
            }

            usleep(200_000); // 200ms
            $waited += 0.2;
        }

        return array_shift($this->available);
    }

    /**
     * Return a browser to the pool after use.
     */
    public function release(Browser $browser): void
    {
        $this->available[] = $browser;
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
                // Already closed - nothing to do.
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
