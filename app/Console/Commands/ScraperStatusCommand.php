<?php

declare(strict_types=1);

namespace App\Console\Commands;

use App\Repositories\ScraperRunRepository;
use Illuminate\Console\Command;

/**
 * Artisan command to check the status of scrape runs.
 *
 * Usage:
 *   php artisan scraper:status listam
 */
class ScraperStatusCommand extends Command
{
    protected $signature = 'scraper:status
                            {source : Profile name to check (e.g. listam)}';

    protected $description = 'Show the latest scraper run status for a given source';

    /**
     * Looks up and prints the most recent run for the given source.
     */
    public function handle(ScraperRunRepository $repository): int
    {
        $source = $this->argument('source');

        $run = $repository->findLatest($source);

        if ($run === null) {
            $this->warn("No runs found for source: \"{$source}\"");

            return self::SUCCESS;
        }

        $this->table(
            ['ID', 'Source', 'State', 'Started'],
            [[
                $run->id,
                $run->source,
                $run->state->value,
                $run->started_at?->toDateTimeString() ?? $run->created_at->toDateTimeString(),
            ]]
        );

        return self::SUCCESS;
    }
}
