<?php

declare(strict_types=1);

namespace App\Events;

use App\Models\Listing;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

/**
 * Fired when a listing is saved to the database.
 *
 * Carries the saved Listing model.
 * Listeners use this for logging, webhooks, or downstream sync.
 */
class ListingSaved
{
    use Dispatchable, SerializesModels;

    public function __construct(
        public readonly Listing $listing,
    ) {}
}
