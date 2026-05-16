<?php

declare(strict_types=1);

namespace App\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Casts\Attribute;
use Illuminate\Database\Eloquent\Model;

/**
 * Represents a single real estate listing scraped from an external source.
 *
 * Stores normalized property data including location, pricing and specifications.
 * Supports multiple scraping sources via the compound external_id + source unique index.
 */
class Listing extends Model
{
    protected $fillable = [
        'external_id',
        'source',
        'city',
        'district',
        'address',
        'category',
        'listing_type',
        'price',
        'currency',
        'area',
        'rooms',
        'floor',
        'total_floors',
        'building_type',
        'build_year',
        'phone',
        'description',
        'url',
        'status',
        'listed_at',
        'scraped_at',
    ];

    protected $casts = [
        'price' => 'float',
        'area' => 'float',
        'listed_at' => 'datetime',
        'scraped_at' => 'datetime',
    ];

    /**
     * Calculate price per square meter from existing columns.
     *
     * Returns null if area is missing or zero to avoid division by zero.
     */
    protected function pricePerSqm(): Attribute
    {
        return Attribute::make(
            get: fn() => $this->area > 0 ? round($this->price / $this->area, 2) : null
        );
    }

    /**
     * Scope to filter only active listings.
     */
    public function scopeActive(Builder $query): Builder
    {
        return $query->where('status', 'active');
    }

    /**
     * Scope to filter listings by scraping source.
     */
    public function scopeForSource(Builder $query, string $source): Builder
    {
        return $query->where('source', $source);
    }
}
