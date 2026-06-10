<?php

declare(strict_types=1);

namespace App\Scrapers\Exports;

use App\Scrapers\Contracts\ScraperProfileInterface;
use App\Scrapers\Exports\Contracts\ExporterInterface;
use Maatwebsite\Excel\Concerns\FromArray;
use Maatwebsite\Excel\Concerns\WithHeadings;
use Maatwebsite\Excel\Facades\Excel;

/**
 * Uses Maatwebsite Excel which wraps PHPSpreadsheet internally.
 * Column headers are derived automatically from the data keys -
 * no changes needed here when new fields are added to a scraper.
 *
 * Intended for human consumers: clients, analysts, reports.
 * For machine consumption (pipelines, databases) use CsvExporter.
 */
class ExcelExporter implements ExporterInterface
{
    /**
     * An anonymous class is passed to Excel::store() implementing the
     * two Maatwebsite contracts it needs:
     *   FromArray - provides the data rows
     *   WithHeadings - provides the header row
     */
    public function export(array $data, ScraperProfileInterface $profile): string
    {
        $path = $this->buildPath($profile->getName());

        Excel::store(new class($data) implements FromArray, WithHeadings {
            public function __construct(private readonly array $data) {}

            /**
             * Data rows passed to Maatwebsite.
             * array_values() strips associative keys - Maatwebsite
             * expects purely indexed arrays for each row.
             */
            public function array(): array
            {
                return array_map(fn($row) => array_values($row), $this->data);
            }

            /**
             * Header row derived from the first data row's keys.
             * Automatically reflects whatever fields the scraper produces.
             */
            public function headings(): array
            {
                return array_keys($this->data[0] ?? []);
            }
        }, $path, 'local');

        return storage_path('app/' . $path);
    }

    /**
     * The file extension this exporter produces.
     */
    public function extension(): string
    {
        return 'xlsx';
    }

    /**
     * Build the relative file path for Excel::store().
     * Relative to storage/app/
     * Path root configured via SCRAPER_EXPORT_PATH in .env.
     */
    private function buildPath(string $source): string
    {
        $dir  = config('scraper.export_path');
        $date = now()->format('Y_m_d');

        return "{$dir}/{$source}_{$date}.{$this->extension()}";
    }
}
