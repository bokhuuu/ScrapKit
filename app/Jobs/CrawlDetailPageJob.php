<?php

declare(strict_types=1);

namespace App\Jobs;

use App\DTOs\ListingDTO;
use App\Jobs\Middleware\RateLimitedMiddleware;
use App\Jobs\Middleware\ThrottledRetryMiddleware;
use App\Repositories\ListingRepository;
use App\Scrapers\Contracts\ScraperProfileInterface;
use App\Scrapers\Pipeline\ScraperPipeline;
use Illuminate\Bus\Batchable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Queue\Queueable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Log;
use Throwable;

/**
 * Crawls one listing detail page, runs it through the pipeline,
 * and persists the result to the database.
 *
 * Dispatched by CrawlIndexPageJob - one job per URL found on a grid page.
 * These are the most numerous jobs: 50 pages × 20 listings = ~1000 jobs per run.
 */
class CrawlDetailPageJob implements ShouldQueue
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
        private readonly string $url,
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
     * Crawls one listing detail page, processes it through the pipeline,
     * and saves it to the database if it passes validation and isn't
     * a duplicate. Always closes the browser when done, success or not.
     */
    public function handle(ListingRepository $repository): void
    {
        $scraper = app($this->profile->getScraperClass(), [
            'profile' => $this->profile,
        ]);

        $authStrategy = $this->profile->getAuthStrategy($scraper->getBrowser());
        if ($authStrategy !== null) {
            $scraper->setAuthStrategy($authStrategy);
        }

        try {
            $raw = $scraper->fetchDetailPage($this->url);

            $dto = ListingDTO::fromArray($raw);
            $dto->scraperRunId = $this->scraperRunId;

            $pipeline = app(ScraperPipeline::class, ['stages' => $this->profile->getPipelineStages()]);

            $processed = $pipeline->process($dto);

            if ($processed === null) {
                return;
            }

            $repository->updateOrCreate($processed->toArray());
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
        Log::error('CrawlDetailPageJob failed', [
            'source' => $this->profile->getName(),
            'url' => $this->url,
            'run_id' => $this->scraperRunId,
            'error' => $exception->getMessage(),
        ]);
    }
}
