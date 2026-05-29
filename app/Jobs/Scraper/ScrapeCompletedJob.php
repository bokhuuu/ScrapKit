<?php

declare(strict_types=1);

namespace App\Jobs;

use App\Repositories\ScraperRunRepository;
use App\Scrapers\Enums\ScraperState;
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
    use Queueable, InteractsWithQueue, SerializesModels;

    public function __construct(
        private readonly int $scraperRunId,
        private readonly string $source,
    ) {}

    public function handle(ScraperRunRepository $repository): void
    {
        $repository->markAsCompleted($this->scraperRunId);

        Log::info('Scrape run completed', [
            'run_id' => $this->scraperRunId,
            'source' => $this->source,
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
