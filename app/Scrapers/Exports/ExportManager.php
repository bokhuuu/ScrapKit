<?php

declare(strict_types=1);

namespace App\Scrapers\Exports;

use App\Repositories\ListingRepository;
use App\Scrapers\Exports\Contracts\ExporterInterface;
use Illuminate\Support\Facades\Log;

/**
 * Resolves the active profile from config, fetches the scraped listings
 * for the completed run, then calls each configured exporter in turn.
 *
 * Adding a new export format requires only:
 *   1. A class implementing ExporterInterface
 *   2. An entry in config/scraper.php exporters map
 *   3. The format key declared in the profile's getExports()
 *
 * ExportManager itself never changes.
 */
class ExportManager
{
    /**
     * Takes the repository used to fetch the listings that need exporting.
     */
    public function __construct(
        private readonly ListingRepository $repository,
    ) {}

    /**
     * Run all configured exporters for the completed scrape run.
     *
     * Fetches listings scoped to the scraperRunId so exports always
     * reflect exactly what was collected in this run - not the full table.
     */
    public function run(string $source, int $scraperRunId): array
    {
        $profileClass = config('scraper.profiles.'.$source);
        $profile = app($profileClass);

        $listings = $this->repository->findByRun($scraperRunId);

        if ($listings->isEmpty()) {
            Log::warning('ExportManager: no listings found for run.', [
                'source' => $source,
                'scraper_run_id' => $scraperRunId,
            ]);

            return [];
        }

        $data = $listings->map(fn ($listing) => $listing->toArray())->toArray();
        $paths = [];

        foreach ($profile->getExports() as $format) {
            $exporterClass = config('scraper.exporters.'.$format);

            if (! $exporterClass) {
                Log::warning('ExportManager: unknown export format.', [
                    'format' => $format,
                    'source' => $source,
                ]);

                continue;
            }

            /** @var ExporterInterface $exporter */
            $exporter = app($exporterClass);
            $paths[] = $exporter->export($data, $profile);

            Log::info('ExportManager: export complete.', [
                'format' => $format,
                'source' => $source,
                'run_id' => $scraperRunId,
            ]);
        }

        return $paths;
    }
}
