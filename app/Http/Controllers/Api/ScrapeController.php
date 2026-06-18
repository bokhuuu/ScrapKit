<?php

declare(strict_types=1);

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Requests\CancelScrapeRequest;
use App\Http\Requests\StartScrapeRequest;
use App\Repositories\ScraperRunRepository;
use App\Scrapers\ScraperManager;
use Illuminate\Http\JsonResponse;

/**
 * Handles HTTP control of scrape runs.
 *
 * Delegates all business logic to ScraperManager and ScraperRunRepository.
 * This controller only receives, delegates and responds.
 */
class ScrapeController extends Controller
{
    /**
     * Takes the manager that runs/cancels scrapes and the repository
     * used to look up run state.
     */
    public function __construct(
        private readonly ScraperManager $manager,
        private readonly ScraperRunRepository $runRepository,
    ) {}

    /**
     * Start a new scrape run for the given source.
     *
     * Resolves the profile from config, delegates to ScraperManager
     * and returns the created run ID immediately.
     * All scraping happens asynchronously in the queue.
     */
    public function start(StartScrapeRequest $request): JsonResponse
    {
        $source = $request->validated('source');
        $pages = $request->validated('pages');

        $profileClass = config("scraper.profiles.{$source}");
        $profile = app($profileClass);

        $this->manager->run($profile, $pages);

        $run = $this->runRepository->findLatest($source);

        return response()->json([
            'message' => 'Scrape started.',
            'run_id' => $run->id,
            'source' => $source,
            'pages' => $pages ?? $profile->getMaxPages(),
        ], 202);
    }

    /**
     * Return the latest scrape run state for a given source.
     */
    public function status(): JsonResponse
    {
        $source = request()->query('source');
        $run = $this->runRepository->findLatest($source);

        if (! $run) {
            return response()->json(['message' => 'No runs found for this source.'], 404);
        }

        return response()->json([
            'run_id' => $run->id,
            'source' => $run->source,
            'state' => $run->state,
            'started_at' => $run->started_at,
            'finished_at' => $run->finished_at,
            'scraped_pages' => $run->scraped_pages,
            'saved_listings' => $run->saved_listings,
        ]);
    }

    /**
     * Cancel an active scrape run by ID.
     */
    public function cancel(CancelScrapeRequest $request): JsonResponse
    {
        $runId = $request->validated('run_id');
        $run = $this->runRepository->findById($runId);

        $this->manager->cancel($runId);

        return response()->json([
            'message' => 'Scrape run cancelled.',
            'run_id' => $runId,
            'source' => $run->source,
        ]);
    }
}
