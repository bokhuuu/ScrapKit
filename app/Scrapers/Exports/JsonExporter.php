<?php

declare(strict_types=1);

namespace App\Scrapers\Exports;

use App\Scrapers\Contracts\ScraperProfileInterface;
use App\Scrapers\Exports\Contracts\ExporterInterface;

/**
 * Intended for machine consumers - APIs, pipelines, frontend apps,
 * or any system that imports structured data programmatically.
 * Unlike CSV, JSON preserves data types (integers, booleans, nulls).
 * Unlike Excel, it requires no spreadsheet software to parse.
 *
 * No external package required - PHP's json_encode handles everything.
 */
class JsonExporter implements ExporterInterface
{
    /**
     * Write data to a JSON file in the configured export directory.
     * Creates the directory if it does not exist.
     * Output is pretty-printed for readability and debuggability.
     */
    public function export(array $data, ScraperProfileInterface $profile): string
    {
        $path = $this->buildPath($profile->getName());

        $directory = dirname($path);
        if (! is_dir($directory)) {
            mkdir($directory, 0755, true);
        }

        file_put_contents(
            $path,
            json_encode($data, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE)
        );

        return $path;
    }

    /**
     * The file extension this exporter produces.
     */
    public function extension(): string
    {
        return 'json';
    }

    /**
     * Build the absolute file path for the export file.
     * Path root configured via SCRAPER_EXPORT_PATH in .env.
     */
    private function buildPath(string $source): string
    {
        $dir  = storage_path('app/' . config('scraper.export_path'));
        $date = now()->format('d_m_Y');

        return "{$dir}/{$source}_{$date}.{$this->extension()}";
    }
}
