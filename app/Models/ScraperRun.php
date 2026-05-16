<?php

declare(strict_types=1);

namespace App\Models;

use App\Enums\ScraperState;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Builder;

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

    public function scopeForSource(Builder $query, string $source): Builder
    {
        return $query->where('source', $source);
    }

    public function scopeForState(Builder $query, ScraperState $state): Builder
    {
        return $query->where('state', $state);
    }
}
