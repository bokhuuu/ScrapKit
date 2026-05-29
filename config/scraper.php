<?php

declare(strict_types=1);

return [

    /*
    |--------------------------------------------------------------------------
    | ChromeDriver Connection
    |--------------------------------------------------------------------------
    |
    | URL where ChromeDriver is listening. Default port is 9515.
    | Override in .env if running ChromeDriver in Docker or on a remote host.
    |
    */

    'chromedriver_url' => env('CHROMEDRIVER_URL', 'http://localhost:9515'),

    /*
    |--------------------------------------------------------------------------
    | Browser Timeouts
    |--------------------------------------------------------------------------
    |
    | connect_timeout_ms - how long to wait when opening a ChromeDriver session.
    | request_timeout_ms - how long to wait for each WebDriver command to complete.
    | element_wait_timeout_s - how long waitFor() polls before throwing.
    |
    | All values configurable per environment. Production may need higher
    | values; local development can be lower for faster feedback.
    |
    */

    'browser_connect_timeout_ms' => (int) env('BROWSER_CONNECT_TIMEOUT_MS', 30_000),
    'browser_request_timeout_ms' => (int) env('BROWSER_REQUEST_TIMEOUT_MS', 30_000),
    'element_wait_timeout_s' => (int) env('ELEMENT_WAIT_TIMEOUT_S', 10),

    /*
    |--------------------------------------------------------------------------
    | Browser Window
    |--------------------------------------------------------------------------
    |
    | Window size affects which CSS breakpoint the site renders at.
    | Sites sometimes hide elements on mobile widths- use desktop size.
    |
    */

    'browser_window_size' => env('BROWSER_WINDOW_SIZE', '1920,1080'),

    /*
    |--------------------------------------------------------------------------
    | Stealth- User Agent
    |--------------------------------------------------------------------------
    |
    | The browser string sent with every request.
    | Update this in .env when Chrome releases a new major version.
    | Never use the default HeadlessChrome string- it is instantly detectable.
    |
    */

    'user_agent' => env(
        'SCRAPER_USER_AGENT',
        'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/124.0.0.0 Safari/537.36'
    ),

    /*
    |--------------------------------------------------------------------------
    | Retry Logic
    |--------------------------------------------------------------------------
    |
    | When a browser action fails (element not found, timeout, network blip),
    | BaseScraper::retry() will attempt it this many times before giving up.
    | retry_delay_ms is the wait between attempts.
    |
    */

    'retry_attempts' => (int) env('SCRAPER_RETRY_ATTEMPTS', 3),
    'retry_delay_ms' => (int) env('SCRAPER_RETRY_DELAY_MS', 1_000),

    /*
    |--------------------------------------------------------------------------
    | Human-like Pacing
    |--------------------------------------------------------------------------
    |
    | Default delay between page requests in seconds.
    | Each site profile can override this via getRequestDelay().
    | Jitter of ±30% is applied automatically in BaseScraper::pauseForHuman().
    | Increase on sites with aggressive rate limiting.
    |
    */

    'default_request_delay_s' => (int) env('SCRAPER_REQUEST_DELAY', 2),

    /*
    |--------------------------------------------------------------------------
    | Browser Pool
    |--------------------------------------------------------------------------
    |
    | Maximum number of concurrent browser instances.
    | Each instance uses ~150-200MB RAM- size this to your server.
    | Phase 4.2 (BrowserPool) reads this value.
    |
    */

    'browser_pool_size' => (int) env('BROWSER_POOL_SIZE', 3),

    /*
    |--------------------------------------------------------------------------
    | Queue Settings
    |--------------------------------------------------------------------------
    |
    | Default concurrency, retry attempts, and job timeout for queue jobs.
    | Each site profile can override these via getQueueConfig().
    | Adjust timeout for slow sites that need more time per page.
    |
    */

    'default_concurrency' => (int) env('SCRAPER_CONCURRENCY', 3),
    'default_retry_times' => (int) env('SCRAPER_RETRY_TIMES', 3),
    'default_timeout_s'   => (int) env('SCRAPER_TIMEOUT_S', 60),

    /*
    |--------------------------------------------------------------------------
    | Scraper Profiles
    |--------------------------------------------------------------------------
    |
    | Maps profile name strings to their class implementations.
    | Add a new entry here to register a new scraping target.
    | No other files need to change.
    |
    | 'name' => ProfileClass::class
    |
    */

    'profiles' => [
        'listam' => \App\Scrapers\Profiles\ListAmProfile::class,
    ],
];
