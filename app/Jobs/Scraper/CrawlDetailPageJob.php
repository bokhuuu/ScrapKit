<?php

declare(strict_types=1);

namespace App\Jobs;

use App\DTOs\ListingDTO;
use App\Jobs\Middleware\ThrottledRetryMiddleware;
use App\Repositories\ListingRepository;
use App\Scrapers\Browser\ListAmScraper;
use App\Scrapers\Contracts\ScraperProfileInterface;
use App\Scrapers\Pipeline\ScraperPipeline;
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
    use Queueable, InteractsWithQueue, SerializesModels;

    public int $tries;
    public int $timeout;

    public function __construct(
        private readonly ScraperProfileInterface $profile,
        private readonly string $url,
        private readonly int $scraperRunId,
    ) {
        $config = $profile->getQueueConfig();
        $this->tries = $config['retry_times'] ?? config('scraper.default_retry_times');
        $this->timeout = $config['timeout_s'] ?? config('scraper.default_timeout_s');
    }

    public function middleware(): array
    {
        $config = $this->profile->getQueueConfig();

        return [
            new ThrottledRetryMiddleware(),
        ];
    }

    public function handle(ListingRepository $repository): void
    {
        $scraper = new ListAmScraper($this->profile);

        try {
            $raw = $scraper->crawlDetailPage($this->url);

            $dto = ListingDTO::fromArray($raw);

            $pipeline = new ScraperPipeline(
                $this->profile->getPipelineStages()
            );

            $processed = $pipeline->process($dto);

            if ($processed === null) {
                return;
            }

            $repository->updateOrCreate($processed->toArray());
        } finally {
            $scraper->closeBrowser();
        }
    }

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
