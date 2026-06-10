<?php

declare(strict_types=1);

namespace App\Scrapers\Exports\Contracts;

use App\Scrapers\Contracts\ScraperProfileInterface;

/**
 * ExportManager depends only on this interface - never on concrete exporters.
 * Adding a new format (XML, PDF, Google Sheets) requires zero changes to
 * ExportManager. Create the class, implement this interface, declare it
 * in the profile's getExports() - done.
 */
interface ExporterInterface
{
    /**
     * Receives the full dataset and the active profile.
     * Profile is used to read the source name for filename generation.
     */
    public function export(array $data, ScraperProfileInterface $profile): string;

    /**
     * The file extension this exporter produces.
     * Used by ExportManager to build the output filename.
     * Example: 'xlsx', 'csv', 'json'
     */
    public function extension(): string;
}
