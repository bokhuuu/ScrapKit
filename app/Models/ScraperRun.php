<?php

declare(strict_types=1);

namespace App\Models;

use App\Enums\ScraperState;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Builder;

/**
 * Tracks a single scraper execution - its state, progress and outcome.
 *
 * Created when a scrape starts, updated as it progresses, finalized on completion or failure.
 */
class ScraperRun extends Model
{
    protected $fillable = [
        'source',
        'state',
        'scraped_pages',
        'saved_listings',
        'error',
        'started_at',
        'finished_at',
    ];

    protected $casts = [
        'state' => ScraperState::class,
        'started_at' => 'datetime',
        'finished_at' => 'datetime',
    ];

    /**
     * Scope to filter runs by scraping source.
     */
    public function scopeForSource(Builder $query, string $source): Builder
    {
        return $query->where('source', $source);
    }

    /**
     * Scope to filter runs by their current state.
     *
     */
    public function scopeForState(Builder $query, ScraperState $state): Builder
    {
        return $query->where('state', $state);
    }
}
