<?php

declare(strict_types=1);

namespace App\Repositories;

use App\Events\ListingSaved;
use App\Models\Listing;
use Illuminate\Support\Collection;

/**
 * Handles all database interactions for the Listing model.
 *
 * All layers (pipeline, jobs, export) interact with listings through this class only.
 * Direct Eloquent calls outside this repository are not permitted.
 */
class ListingRepository
{
    public function save(array $data): Listing
    {
        return Listing::create($data);
    }

    /**
     * Update an existing listing or create a new one if not found.
     *
     * Matches on external_id + source compound key.
     * Used by the pipeline as the primary save method to handle re-scrapes cleanly.
     *
     * @param  array  $data  Expects output of ListingDTO::toArray()
     */
    public function updateOrCreate(array $data): Listing
    {
        $listing = Listing::updateOrCreate(
            ['external_id' => $data['external_id'], 'source_profile_name' => $data['source_profile_name']],
            $data
        );

        event(new ListingSaved($listing));

        return $listing;
    }

    /**
     * Check if a listing already exists in the database.
     *
     * Used by the deduplication pipeline stage to skip already-scraped listings.
     */
    public function existsByExternalId(string $externalId, string $source): bool
    {
        return Listing::where('external_id', $externalId)
            ->where('source_profile_name', $source)
            ->exists();
    }

    /**
     * Retrieve all active listings for a given source.
     */
    public function findBySource(string $source): Collection
    {
        return Listing::forSource($source)->get();
    }

    /**
     * Count total listings for a given source.
     *
     * Used by ScraperManager to record saved_listings count on run completion.
     */
    public function countBySource(string $source): int
    {
        return Listing::where('source_profile_name', $source)->count();
    }
}
