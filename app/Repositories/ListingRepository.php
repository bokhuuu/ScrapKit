<?php

declare(strict_types=1);

namespace App\Repositories;

use App\Events\ListingSaved;
use App\Models\Listing;
use Illuminate\Pagination\LengthAwarePaginator;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Cache;

/**
 * Handles all database interactions for the Listing model.
 *
 * All layers (pipeline, jobs, export) interact with listings through this class only.
 * Direct Eloquent calls outside this repository are not permitted.
 */
class ListingRepository
{
    /**
     * Create a brand new listing record.
     *
     * Rarely used directly - updateOrCreate() is the normal save path
     * since it handles re-scrapes without creating duplicates.
     */
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
     * Paginated listings for a source, optionally filtered by
     * district and/or a price range.
     */
    public function paginateBySource(string $source, array $filters = [], int $perPage = 50): LengthAwarePaginator
    {
        $query = Listing::where('source_profile_name', $source);

        if (! empty($filters['district'])) {
            $query->where('district', $filters['district']);
        }

        if (! empty($filters['min_price'])) {
            $query->where('price', '>=', $filters['min_price']);
        }

        if (! empty($filters['max_price'])) {
            $query->where('price', '<=', $filters['max_price']);
        }

        return $query->paginate($perPage);
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

    /**
     * Count listings saved by one specific scrape run.
     *
     * Used by ScrapeCompletedJob to report accurate per-run results,
     * instead of the all-time total for the source.
     */
    public function countByRun(int $scraperRunId): int
    {
        return Listing::where('scraper_run_id', $scraperRunId)->count();
    }

    /**
     * Retrieve all listings saved by one specific scrape run.
     *
     * Used by ExportManager so each export reflects only what
     * this run collected, not the entire historical dataset.
     */
    public function findByRun(int $scraperRunId): Collection
    {
        return Listing::where('scraper_run_id', $scraperRunId)->get();
    }

    /**
     * Average price per sqm grouped by district for a given source.
     *
     * Result is cached for 1 hour - expensive aggregation across
     * potentially thousands of rows. Cache invalidates automatically
     * so next API call recalculates fresh.
     *
     * Used by GET /api/listings/stats.
     */
    public function getDistrictPriceStats(string $source): Collection
    {
        $key = "scraper:stats:district_prices:{$source}";

        return Cache::remember($key, now()->addHour(), function () use ($source) {
            return Listing::where('source_profile_name', $source)
                ->whereNotNull('district')
                ->whereNotNull('price_per_sqm')
                ->selectRaw('district, ROUND(AVG(price_per_sqm), 2) as avg_price_per_sqm, COUNT(*) as listing_count')
                ->groupBy('district')
                ->orderByDesc('avg_price_per_sqm')
                ->get();
        });
    }

    /**
     * Count listings where a specific field is null for a given source.
     *
     * Used by DriftDetector to calculate null rates on monitored fields.
     */
    public function countNullField(string $source, string $field): int
    {
        return Listing::where('source_profile_name', $source)
            ->whereNull($field)
            ->count();
    }
}
