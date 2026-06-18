<?php

declare(strict_types=1);

namespace App\Jobs;

use App\Jobs\Middleware\RateLimitedMiddleware;
use App\Jobs\Middleware\ThrottledRetryMiddleware;
use App\Scrapers\Contracts\ScraperProfileInterface;
use Illuminate\Bus\Batchable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Queue\Queueable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Log;
use Throwable;

/**
 * Crawls one index page (listing grid) and dispatches
 * one CrawlDetailPageJob per URL found on that page.
 *
 * Dispatched by ScraperManager - one job per page number.
 * 50 pages = 50 of these jobs, all processed in parallel.
 */
class CrawlIndexPageJob implements ShouldQueue
{
    use Batchable, InteractsWithQueue, Queueable, SerializesModels;

    public int $tries;

    public int $timeout;

    private int $concurrency;

    /**
     * Sets up the job with the profile, page number, and run ID,
     * pulling retry/timeout/concurrency settings from the profile's
     * queue config (falling back to the global defaults).
     */
    public function __construct(
        private readonly ScraperProfileInterface $profile,
        private readonly int $page,
        private readonly int $scraperRunId,
    ) {
        $config = $profile->getQueueConfig();
        $this->tries = $config['retry_times'] ?? config('scraper.default_retry_times');
        $this->timeout = $config['timeout'] ?? config('scraper.default_timeout_s');
        $this->concurrency = $config['concurrency'] ?? config('scraper.default_concurrency');
    }

    /**
     * Rate-limits how many of this job can run at once per source,
     * and applies backoff between retries.
     */
    public function middleware(): array
    {
        return [
            new RateLimitedMiddleware(
                source: $this->profile->getName(),
                maxConcurrent: $this->concurrency,
            ),

            new ThrottledRetryMiddleware,
        ];
    }

    /**
     * Crawls the index page for listing URLs, queues one detail job
     * per URL into the same batch, then always closes the browser
     * whether or not the crawl succeeded.
     */
    public function handle(): void
    {
        $scraper = app($this->profile->getScraperClass(), [
            'profile' => $this->profile,
        ]);

        try {
            $urls = $scraper->crawlIndexPage($this->page);

            $jobs = [];

            foreach ($urls as $url) {
                $jobs[] = new CrawlDetailPageJob(
                    profile: $this->profile,
                    url: $url,
                    scraperRunId: $this->scraperRunId,
                );
            }

            if (! empty($jobs) && $this->batch()) {
                $this->batch()->add($jobs);
            }
        } finally {
            $scraper->closeBrowser();
        }
    }

    /**
     * Logs the failure with enough context to know which page and
     * run it belongs to.
     */
    public function failed(Throwable $exception): void
    {
        Log::error('CrawlIndexPageJob failed', [
            'source' => $this->profile->getName(),
            'page' => $this->page,
            'run_id' => $this->scraperRunId,
            'error' => $exception->getMessage(),
        ]);
    }
}
