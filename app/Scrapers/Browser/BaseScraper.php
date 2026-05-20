<?php

declare(strict_types=1);

namespace App\Scrapers\Browser;

use App\Scrapers\Contracts\ScraperProfileInterface;
use Closure;
use Facebook\WebDriver\Chrome\ChromeOptions;
use Facebook\WebDriver\Remote\DesiredCapabilities;
use Facebook\WebDriver\Remote\RemoteWebDriver;
use Laravel\Dusk\Browser;
use Throwable;

/**
 * Abstract base class for all site-specific scrapers.
 *
 * Owns the Dusk browser instance and centralizes every browser interaction:
 * navigation, element reading, clicking, waiting and retry logic.
 *
 * Every new scraping target extends this class and implements
 * crawlIndexPage() and crawlDetailPage(). Nothing else changes.
 *
 * Configuration is pulled entirely from config/scraper.php -
 * no hardcoded values anywhere in this class.
 */
abstract class BaseScraper
{
    protected Browser $browser;

    /**
     * Inject the site profile so child scrapers can access
     * URLs, selectors and delays without hardcoding them.
     */
    public function __construct(protected readonly ScraperProfileInterface $profile)
    {
        $this->browser = $this->createBrowser();
    }

    /**
     * Crawl one index page (the listing grid) and return
     * an array of detail-page URLs found on that page.
     */
    abstract public function crawlIndexPage(int $page): array;

    /**
     * Crawl one detail page (a single listing) and return
     * a raw key-value array of all extracted fields.
     */
    abstract public function crawlDetailPage(string $url): array;

    /**
     * Spin up a real Chrome browser via ChromeDriver.
     *
     * All Chrome arguments come from chromeArguments() so subclasses
     * can override them (e.g. StealthConfig adds extra stealth flags).
     */
    protected function createBrowser(): Browser
    {
        $options = new ChromeOptions();
        $options->addArguments($this->chromeArguments());

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

    /**
     * Chrome flags used for every browser instance.
     *
     * Subclasses can override this to add stealth flags,
     * proxy settings, or custom window sizes.
     */
    protected function chromeArguments(): array
    {
        return [
            '--headless',
            '--no-sandbox',
            '--disable-gpu',
            '--disable-dev-shm-usage',
            '--window-size=' . config('scraper.browser_window_size'),
        ];
    }

    /**
     * Explicitly close the browser and release the ChromeDriver session.
     * Call this when the scrape run is finished.
     */
    public function closeBrowser(): void
    {
        $this->browser->quit();
    }

    /**
     * Safety net - if closeBrowser() was never called explicitly,
     * PHP's destructor closes the session so ChromeDriver is never left hanging.
     */
    public function __destruct()
    {
        if (isset($this->browser)) {
            try {
                $this->browser->quit();
            } catch (Throwable) {
                // Browser may already be closed - nothing to do.
            }
        }
    }

    /**
     * Navigate to a URL and pause briefly to simulate human reading speed.
     * The pause also gives JS time to finish rendering before we read anything.
     */
    protected function navigate(string $url): void
    {
        $this->browser->visit($url);
        $this->pauseForHuman();
    }

    /**
     * Read the visible text content of an element.
     */
    protected function getText(string $selector): string
    {
        return $this->browser->text($selector);
    }

    /**
     * Read an HTML attribute from an element (e.g. content, href, data-id).
     */
    protected function getAttribute(string $selector, string $attribute): string
    {
        return $this->browser->attribute($selector, $attribute);
    }

    /**
     * Click an element and wait for the page to respond.
     */
    protected function click(string $selector): void
    {
        $this->browser->click($selector);
    }

    /**
     * Wait until a CSS selector appears in the DOM.
     * Times out after scraper.element_wait_timeout_s seconds.
     */
    protected function waitFor(string $selector, ?int $seconds = null): void
    {
        $this->browser->waitFor(
            selector: $selector,
            seconds: $seconds ?? config('scraper.element_wait_timeout_s'),
        );
    }

    /**
     * Wait until a specific string appears anywhere on the page.
     */
    protected function waitForText(string $text, ?int $seconds = null): void
    {
        $this->browser->waitForText(
            text: $text,
            seconds: $seconds ?? config('scraper.element_wait_timeout_s'),
        );
    }

    /**
     * Check whether an element exists in the DOM right now.
     * Useful for optional fields (e.g. phone number, floor, area).
     */
    protected function isPresent(string $selector): bool
    {
        return $this->browser->element($selector) !== null;
    }

    /**
     * Get the full raw HTML source of the current page.
     * Useful for bulk parsing with DomDocument or regex fallback.
     */
    protected function getPageSource(): string
    {
        return $this->browser->driver->getPageSource();
    }

    /**
     * Wrap any browser action in retry logic.
     *
     * If the action throws, it waits $delayMs milliseconds and tries again.
     * After all attempts are exhausted, the last exception is re-thrown
     * so the queue job can handle it (log, retry job, or fail).
     *
     * Usage:
     *   $text = $this->retry(fn () => $this->getText('.price'));
     */
    protected function retry(Closure $action, ?int $attempts = null, ?int $delayMs = null): mixed
    {
        $resolvedAttempts = $attempts ?? (int) config('scraper.retry_attempts');
        $resolvedDelay    = $delayMs  ?? (int) config('scraper.retry_delay_ms');

        $lastException = new \RuntimeException('All retry attempts failed.');

        for ($i = 0; $i < $resolvedAttempts; $i++) {
            try {
                return $action();
            } catch (Throwable $e) {
                $lastException = $e;

                if ($i < $resolvedAttempts - 1) {
                    usleep($resolvedDelay * 1000);
                }
            }
        }

        throw $lastException;
    }

    /**
     * Sleep for a random duration between requests.
     *
     * Random range (not fixed delay) makes the scraper harder to fingerprint.
     * Min and max come from config - tunable per environment or site.
     */
    protected function pauseForHuman(): void
    {
        $min = (int) config('scraper.delay_min_ms');
        $max = (int) config('scraper.delay_max_ms');

        usleep(random_int($min, $max) * 1000);
    }
}
