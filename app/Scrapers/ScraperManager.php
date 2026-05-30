<?php

declare(strict_types=1);

namespace App\Scrapers;

use App\Enums\ScraperState;
use App\Jobs\CrawlIndexPageJob;
use App\Jobs\ScrapeCompletedJob;
use App\Repositories\ScraperRunRepository;
use App\Scrapers\Contracts\ScraperProfileInterface;
use Illuminate\Bus\Batch;
use Illuminate\Support\Facades\Bus;
use Illuminate\Support\Facades\Log;
use Throwable;

/**
 * Orchestrates a full scrape run for a given profile.
 *
 * Responsibilities:
 *   - Creates a ScraperRun DB record to track the run lifecycle
 *   - Builds one CrawlIndexPageJob per page number
 *   - Dispatches all jobs as a Bus batch for parallel processing
 *   - Wires ScrapeCompletedJob via the batch then() callback
 *   - Marks the run as failed if the batch catch() fires
 */
class ScraperManager
{
    public function __construct(
        private readonly ScraperRunRepository $runRepository,
    ) {}

    /**
     * Start a scrape run for the given profile.
     *
     * Creates a DB record, dispatches N index page jobs as a batch,
     * and returns immediately - all processing happens asynchronously in the queue.
     */
    public function run(ScraperProfileInterface $profile, ?int $pages = null): void
    {
        $pages ??= $profile->getMaxPages();

        $run = $this->runRepository->save([
            'source' => $profile->getName(),
            'state' => ScraperState::Running,
            'started_at' => now(),
        ]);

        Log::info('Scrape run started', [
            'run_id' => $run->id,
            'source' => $profile->getName(),
            'pages'  => $pages,
        ]);

        $jobs = [];

        for ($page = 1; $page <= $pages; $page++) {
            $jobs[] = new CrawlIndexPageJob(
                profile: $profile,
                page: $page,
                scraperRunId: $run->id,
            );
        }

        Bus::batch($jobs)
            ->then(function (Batch $batch) use ($run, $profile): void {
                ScrapeCompletedJob::dispatch(
                    scraperRunId: $run->id,
                    source: $profile->getName(),
                );
            })
            ->catch(function (Batch $batch, Throwable $e) use ($run, $profile): void {
                $this->runRepository->markAsFailed($run->id, $e->getMessage());

                Log::error('Scrape batch failed', [
                    'run_id' => $run->id,
                    'source' => $profile->getName(),
                    'error'  => $e->getMessage(),
                ]);
            })
            ->name($this->buildBatchName($profile->getName(), $run->id))
            ->dispatch();
    }

    /**
     * Cancel an active scrape run.
     *
     * Marks the DB record as cancelled. Already-dispatched jobs that are
     * mid-execution will complete, but no new jobs will be dispatched
     * after cancellation is detected.
     */
    public function cancel(int $runId): void
    {
        $this->runRepository->markAsCancelled($runId);

        Log::info('Scrape run cancelled', ['run_id' => $runId]);
    }

    private function buildBatchName(string $source, int $runId): string
    {
        return str_replace(
            ['{source}', '{id}'],
            [$source, $runId],
            config('scraper.batch_name_pattern'),
        );
    }
}
