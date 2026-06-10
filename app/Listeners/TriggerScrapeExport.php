<?php

declare(strict_types=1);

namespace App\Listeners;

use App\Events\ScrapeCompleted;
use App\Scrapers\Exports\ExportManager;
use Illuminate\Support\Facades\Log;

/**
 * Delegates entirely to ExportManager - this listener's only job is
 * to bridge the event system and the export layer.
 * Which formats run is declared in the profile's getExports().
 */
class TriggerScrapeExport
{
    public function __construct(
        private readonly ExportManager $exportManager,
    ) {}

    /**
     * Trigger exports for the completed scrape run.
     * Logs all generated file paths on success.
     */
    public function handle(ScrapeCompleted $event): void
    {
        $paths = $this->exportManager->run($event->source, $event->scraperRunId);

        Log::info('TriggerScrapeExport: exports generated.', [
            'source' => $event->source,
            'run_id' => $event->scraperRunId,
            'files' => $paths,
        ]);
    }
}
