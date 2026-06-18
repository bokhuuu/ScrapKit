<?php

declare(strict_types=1);

namespace App\Console\Commands;

use App\Repositories\ScraperRunRepository;
use App\Scrapers\ScraperManager;
use Illuminate\Console\Command;

/**
 * Artisan command to cancel an active scrape run.
 *
 * Usage:
 *   php artisan scraper:cancel 42
 */
class ScraperCancelCommand extends Command
{
    protected $signature = 'scraper:cancel
                            {run_id : The ID of the scraper run to cancel}';

    protected $description = 'Cancel an active scrape run by its ID';

    /**
     * Cancels the run if it's still active, or tells you it's
     * already finished if there's nothing left to cancel.
     */
    public function handle(ScraperManager $manager, ScraperRunRepository $repository): int
    {
        $runId = (int) $this->argument('run_id');

        $run = $repository->findById($runId);

        if ($run === null) {
            $this->error("No scraper run found with ID: {$runId}");

            return self::FAILURE;
        }

        if ($run->state->isTerminal()) {
            $this->warn("Run #{$runId} is already {$run->state->value} — nothing to cancel.");

            return self::SUCCESS;
        }

        $manager->cancel($runId);

        $this->info("Run #{$runId} has been cancelled.");

        return self::SUCCESS;
    }
}
