<?php

declare(strict_types=1);

namespace App\Events;

use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

/**
 * Fires if a scrape batch fails.
 *
 * Carries the run ID, source name and error itself.
 * Listeners use this to notify about error.
 */
class ScrapeFailed
{
    use Dispatchable, SerializesModels;

    public function __construct(
        public readonly int $scraperRunId,
        public readonly string $source,
        public readonly string $errorMessage,
    ) {}
}
