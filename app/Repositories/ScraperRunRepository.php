<?php

declare(strict_types=1);

namespace App\Repositories;

use App\Enums\ScraperState;
use App\Models\ScraperRun;
use Illuminate\Support\Collection;

/**
 * Handles all database interactions for the ScraperRun model.
 *
 * Used by ScraperManager to track scrape execution lifecycle from start to finish.
 */
class ScraperRunRepository
{
    /**
     * Create a new scraper run record.
     *
     * Called at the start of each scrape. Initial state is always pending.
     */
    public function save(array $data): ScraperRun
    {
        return ScraperRun::create($data);
    }

    /**
     * Update the state of an existing scraper run.
     *
     * Called at each lifecycle transition: pending → running → completed / failed.
     */
    public function updateState(int $id, ScraperState $state): void
    {
        ScraperRun::where('id', $id)->update(['state' => $state]);
    }

    /**
     * Retrieve all scraper runs for a given source.
     */
    public function findBySource(string $source): Collection
    {
        return ScraperRun::forSource($source)->get();
    }

    /**
     * Retrieve the most recent scraper run for a given source.
     *
     * Returns null if no runs exist for that source yet.
     */
    public function findLatest(string $source): ?ScraperRun
    {
        return ScraperRun::where('source', $source)->latest()->first();
    }
}
