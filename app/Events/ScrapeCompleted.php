<?php

declare(strict_types=1);

namespace App\Events;

use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

/**
 * Fired when a scrape batch completes successfully.
 *
 * Carries the run ID, source name and final listing count.
 * Listeners use this to send notifications and trigger exports.
 */
class ScrapeCompleted
{
    use Dispatchable, SerializesModels;

    public function __construct(
        public readonly int $scraperRunId,
        public readonly string $source,
        public readonly int $listingCount,
    ) {}
}
