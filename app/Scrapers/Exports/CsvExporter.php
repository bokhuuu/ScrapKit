<?php

declare(strict_types=1);

namespace App\Scrapers\Exports;

use App\Scrapers\Contracts\ScraperProfileInterface;
use App\Scrapers\Exports\Contracts\ExporterInterface;

/**
 * Exports scraped listing data to a CSV file.
 *
 * Uses PHP's native fputcsv - no external package required.
 * Column headers are derived automatically from the data keys,
 * so no changes are needed here when new fields are added to a scraper.
 */
class CsvExporter implements ExporterInterface
{
    /**
     * Write data to a CSV file in the configured export directory.
     * Creates the directory if it does not exist.
     */
    public function export(array $data, ScraperProfileInterface $profile): string
    {
        $path = $this->buildPath($profile->getName());

        $directory = dirname($path);
        if (! is_dir($directory)) {
            mkdir($directory, 0755, true);
        }

        $handle = fopen($path, 'w');

        fputcsv($handle, array_keys($data[0] ?? []));

        foreach ($data as $row) {
            fputcsv($handle, array_values($row));
        }

        fclose($handle);

        return $path;
    }

    /**
     * The file extension this exporter produces.
     * Used by ExportManager to build the output filename.
     */
    public function extension(): string
    {
        return 'csv';
    }

    /**
     * Build the absolute file path for the export file.
     * Format: storage/app/{export_path}/{source}_{date}.csv
     * Path root is configured via SCRAPER_EXPORT_PATH in .env.
     */
    private function buildPath(string $source): string
    {
        $dir  = storage_path('app/' . config('scraper.export_path'));
        $date = now()->format('Y_m_d');

        return "{$dir}/{$source}_{$date}.{$this->extension()}";
    }
}
