<?php

declare(strict_types=1);

namespace App\Scrapers\Browser;

use Facebook\WebDriver\Chrome\ChromeOptions;
use Facebook\WebDriver\Remote\DesiredCapabilities;
use Facebook\WebDriver\Remote\RemoteWebDriver;
use Illuminate\Support\Facades\Redis;
use Laravel\Dusk\Browser;

/**
 * Manages a fixed pool of reusable Chrome browser sessions.
 *
 * Instead of creating and destroying a browser per job, BrowserPool
 * creates N browsers on initialization and keeps them alive permanently.
 * Jobs acquire a session ID from Redis, reconnect to the existing browser,
 * scrape, then release the session ID back to Redis.
 *
 * Redis coordinates acquisition atomically - two jobs can never
 * receive the same session ID simultaneously.
 */
class BrowserPool
{
    private const POOL_KEY = 'scraper:browser_pool';

    private const ACQUIRE_TIMEOUT_S = 30;

    public function __construct(
        private readonly int $size,
    ) {}

    /**
     * Create all browser instances and push their session IDs into Redis.
     *
     * Called once on application boot or before a scrape run starts.
     * Safe to call multiple times - clears existing pool first.
     */
    public function initialize(): void
    {
        Redis::del(self::POOL_KEY);

        for ($i = 0; $i < $this->size; $i++) {
            $driver = $this->createDriver();
            Redis::rpush(self::POOL_KEY, $driver->getSessionID());
        }
    }

    /**
     * Acquire a browser from the pool.
     *
     * Blocks until a session ID is available (up to ACQUIRE_TIMEOUT_S seconds).
     * Reconnects to the existing Chrome session - no new browser is launched.
     */
    public function acquire(): Browser
    {
        $result = Redis::blpop(self::POOL_KEY, self::ACQUIRE_TIMEOUT_S);

        if ($result === null) {
            throw new \RuntimeException('BrowserPool: timed out waiting for available browser.');
        }

        $sessionId = $result[1];

        $driver = RemoteWebDriver::createBySessionID(
            $sessionId,
            config('scraper.chromedriver_url'),
        );

        return new Browser($driver);
    }

    /**
     * Return a browser to the pool after use.
     *
     * Pushes the session ID back into Redis so the next waiting job can acquire it.
     * Does not close the browser - it stays alive for reuse.
     */
    public function release(Browser $browser): void
    {
        $sessionId = $browser->driver->getSessionID();
        Redis::rpush(self::POOL_KEY, $sessionId);
    }

    /**
     * Shut down all browser sessions and clear the pool from Redis.
     *
     * Called on application shutdown or after a scrape run completes.
     */
    public function teardown(): void
    {
        while (true) {
            $result = Redis::lpop(self::POOL_KEY);

            if ($result === null) {
                break;
            }

            try {
                $driver = RemoteWebDriver::createBySessionID(
                    $result,
                    config('scraper.chromedriver_url'),
                );
                $driver->quit();
            } catch (\Throwable) {
            }
        }
    }

    /**
     * Spin up a single Chrome instance with stealth configuration.
     */
    private function createDriver(): RemoteWebDriver
    {
        $options = new ChromeOptions;
        $options->addArguments([
            '--headless',
            '--no-sandbox',
            '--disable-gpu',
            '--disable-dev-shm-usage',
            '--window-size=' . config('scraper.browser_window_size'),
        ]);

        $capabilities = DesiredCapabilities::chrome();
        $capabilities->setCapability(ChromeOptions::CAPABILITY, $options);

        return RemoteWebDriver::create(
            config('scraper.chromedriver_url'),
            $capabilities,
            (int) config('scraper.browser_connect_timeout_ms'),
            (int) config('scraper.browser_request_timeout_ms'),
        );
    }
}
