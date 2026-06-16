<?php

declare(strict_types=1);

namespace App\Jobs;

use App\Events\ScrapeCompleted;
use App\Repositories\ListingRepository;
use App\Repositories\ScraperRunRepository;
use App\Scrapers\DriftDetector;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Queue\Queueable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Log;
use Throwable;

/**
 * Fires when all CrawlDetailPageJobs for a run have completed.
 *
 * Marks the ScraperRun as finished and logs a summary.
 * Triggered by ScraperManager via Bus::batch()->then() callback.
 * Will also fire export and notification events from here.
 */
class ScrapeCompletedJob implements ShouldQueue
{
    use InteractsWithQueue, Queueable, SerializesModels;

    public function __construct(
        private readonly int $scraperRunId,
        private readonly string $source,
        private readonly int $scrapedPages,
    ) {
        $this->onQueue('completed');
    }

    public function handle(ScraperRunRepository $runRepository, ListingRepository $listingRepository, DriftDetector $driftDetector): void
    {
        $listingCount = $listingRepository->countBySource($this->source);

        $runRepository->markAsCompleted(
            id: $this->scraperRunId,
            savedListings: $listingCount,
            scrapedPages: $this->scrapedPages,
        );

        $driftDetector->check($this->scraperRunId, $this->source, $listingCount);

        event(new ScrapeCompleted(
            scraperRunId: $this->scraperRunId,
            source: $this->source,
            listingCount: $listingCount,
        ));

        Log::info('Scrape run completed', [
            'run_id' => $this->scraperRunId,
            'source' => $this->source,
            'listings' => $listingCount,
            'pages' => $this->scrapedPages,
        ]);
    }

    public function failed(Throwable $exception): void
    {
        Log::error('ScrapeCompletedJob failed', [
            'run_id' => $this->scraperRunId,
            'source' => $this->source,
            'error' => $exception->getMessage(),
        ]);
    }
}
