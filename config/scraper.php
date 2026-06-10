<?php

declare(strict_types=1);
use App\Scrapers\Exports\CsvExporter;
use App\Scrapers\Exports\ExcelExporter;
use App\Scrapers\Exports\JsonExporter;
use App\Scrapers\Profiles\ListAmProfile;
use App\Scrapers\Profiles\Reports\ColliersExcelReport;

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
    | Request Delay Jitter
    |--------------------------------------------------------------------------
    |
    | Fraction of the base delay applied as random variance (±).
    | 0.3 means ±30% - a 2s delay becomes anywhere from 1.4s to 2.6s.
    | Increase for more unpredictable timing, decrease for more consistent pacing.
    |
    */

    'request_delay_jitter' => (float) env('SCRAPER_DELAY_JITTER', 0.3),

    /*
    |--------------------------------------------------------------------------
    | Stealth - Browser Languages
    |--------------------------------------------------------------------------
    |
    | Languages reported to sites via navigator.languages JS property.
    | Comma-separated. Match the locale of your target site.
    | list.am: en-US,en - Armenian site but English interface targeted.
    | A Georgian site: ka-GE,ka
    |
    */

    'browser_languages' => env('SCRAPER_BROWSER_LANGUAGES', 'en-US,en'),

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
    'default_timeout_s' => (int) env('SCRAPER_TIMEOUT_S', 60),

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
        'listam' => ListAmProfile::class,
    ],

    /*
    |--------------------------------------------------------------------------
    | Job Middleware
    |--------------------------------------------------------------------------
    |
    | rate_limit_window_s  - rolling window for concurrent job throttling.
    | rate_limit_release_s - how long to wait before retrying a throttled job.
    | retry_base_delay_s   - multiplied by attempt count for progressive backoff.
    |                        attempt 1 = 30s, attempt 2 = 60s, attempt 3 = 90s.
    |
    */

    'rate_limit_window_s' => (int) env('SCRAPER_RATE_LIMIT_WINDOW', 1),
    'rate_limit_release_s' => (int) env('SCRAPER_RATE_LIMIT_RELEASE', 5),
    'retry_base_delay_s' => (int) env('SCRAPER_RETRY_BASE_DELAY', 30),

    /*
    |--------------------------------------------------------------------------
    | Default Max Pages
    |--------------------------------------------------------------------------
    |
    | Maximum pages to scrape per run by default.
    | Set to 2 locally for fast testing, 50+ in production for full runs.
    |
    */

    'default_max_pages' => (int) env('SCRAPER_MAX_PAGES', 50),

    /*
    |--------------------------------------------------------------------------
    | Per-Profile Configuration
    |--------------------------------------------------------------------------
    |
    | Site-specific settings and credentials.
    | Add a new entry here for each scraping target.
    | Credentials are never hardcoded - always read from .env.
    |
    */

    'profile_config' => [
        'listam' => [
            'request_delay' => env('LISTAM_REQUEST_DELAY', 3),
            'max_pages' => env('LISTAM_MAX_PAGES', 50),
            'auth' => [
                'email' => env('LISTAM_EMAIL'),
                'password' => env('LISTAM_PASSWORD'),
            ],
        ],
    ],

    /*
    |--------------------------------------------------------------------------
    | Batch Name Pattern
    |--------------------------------------------------------------------------
    |
    | Pattern used to name Laravel Bus batches for identification in logs
    | and Laravel Horizon. Supports two placeholders:
    |   {source} - the profile name (e.g. listam)
    |   {id}     - the scraper run ID
    |
    */
    'batch_name_pattern' => env('SCRAPER_BATCH_NAME_PATTERN', '{source}-run-{id}'),

    /*
    |--------------------------------------------------------------------------
    | Export Path
    |--------------------------------------------------------------------------
    |
    | Directory where exported files are stored, relative to storage/app/.
    | Override via SCRAPER_EXPORT_PATH in .env.
    |
    */
    'export_path' => env('SCRAPER_EXPORT_PATH', 'exports'),

    /*
    |--------------------------------------------------------------------------
    | Exporters Map
    |--------------------------------------------------------------------------
    |
    | Maps format keys to their exporter class implementations.
    | Profiles declare which formats to run via getExports().
    | Add a new entry here to register a new export format globally.
    | No other files need to change.
    |
    | 'format_key' => ExporterClass::class
    |
    */
    'exporters' => [
        'excel' => ExcelExporter::class,
        'csv' => CsvExporter::class,
        'json' => JsonExporter::class,
        'colliers_report' => ColliersExcelReport::class,
    ],
];
