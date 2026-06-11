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

    public function __construct(
        private readonly ScraperProfileInterface $profile,
        private readonly int $page,
        private readonly int $scraperRunId,
    ) {
        $config = $profile->getQueueConfig();
        $this->tries = $config['retry_times'] ?? config('scraper.default_retry_times');
        $this->timeout = $config['timeout_s'] ?? config('scraper.default_timeout_s');
    }

    public function middleware(): array
    {
        return [
            new RateLimitedMiddleware(
                source: $this->profile->getName(),
                maxConcurrent: config('scraper.default_concurrency'),
            ),

            new ThrottledRetryMiddleware,
        ];
    }

    public function handle(): void
    {
        $scraper = app($this->profile->getScraperClass(), ["profile" => $this->profile]);

        try {
            $urls = $scraper->crawlIndexPage($this->page);

            foreach ($urls as $url) {
                CrawlDetailPageJob::dispatch(
                    profile: $this->profile,
                    url: $url,
                    scraperRunId: $this->scraperRunId,
                );
            }
        } finally {
            $scraper->closeBrowser();
        }
    }

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
