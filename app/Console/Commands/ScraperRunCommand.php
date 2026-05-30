<?php

declare(strict_types=1);

namespace App\Console\Commands;

use App\Scrapers\Contracts\ScraperProfileInterface;
use App\Scrapers\ScraperManager;
use Illuminate\Console\Command;

/**
 * Artisan command to start a scrape run.
 *
 * Usage:
 *   php artisan scraper:run listam
 *   php artisan scraper:run listam --pages=10
 */
class ScraperRunCommand extends Command
{
    protected $signature = 'scraper:run
                            {source : Profile name to scrape (e.g. listam)}
                            {--pages= : Number of index pages to crawl (defaults to profile maximum)}';

    protected $description = 'Start a scrape run for the given source profile';

    public function handle(ScraperManager $manager): int
    {
        $source = $this->argument('source');
        $pages  = $this->option('pages') ? (int) $this->option('pages') : null;

        $profile = $this->resolveProfile($source);

        if ($profile === null) {
            $available = implode(', ', array_keys(config('scraper.profiles', [])));
            $this->error("Unknown source: \"{$source}\". Available: {$available}");

            return self::FAILURE;
        }

        $this->info("Starting scrape run for [{$source}]" . ($pages ? " - {$pages} pages" : '') . '...');

        $manager->run($profile, $pages);

        $this->info('Jobs dispatched. Run `php artisan queue:work` to process them.');

        return self::SUCCESS;
    }

    /**
     * Resolve a profile name string to a profile instance.
     *
     * Reads from config/scraper.php - add new profiles there, not here.
     */
    private function resolveProfile(string $source): ?ScraperProfileInterface
    {
        $profiles = config('scraper.profiles', []);

        if (! isset($profiles[$source])) {
            return null;
        }

        return app($profiles[$source]);
    }
}
