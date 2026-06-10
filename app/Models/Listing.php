<?php

declare(strict_types=1);

namespace App\Models;

use Illuminate\Database\Eloquent\Builder;
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
        'source_profile_name',
        'url',
        'listing_type',
        'property_type',

        'price',
        'currency',
        'price_per_sqm',

        'area',

        'rooms',
        'bathrooms',
        'floor',
        'total_floors',
        'ceiling_height',
        'building_type',
        'condition',
        'is_new_building',

        'district',
        'address',

        'phone',
        'agency_name',
        'images',
        'extras',

        'listing_date',
        'scraped_at',
    ];

    protected $casts = [
        'price' => 'float',
        'price_per_sqm' => 'float',
        'area' => 'float',
        'ceiling_height' => 'float',
        'is_new_building' => 'boolean',
        'images' => 'array',
        'extras' => 'array',
        'listing_date' => 'datetime',
        'scraped_at' => 'datetime',
    ];

    /**
     * Scope to filter listings by scraping source.
     */
    public function scopeForSource(Builder $query, string $source): Builder
    {
        return $query->where('source_profile_name', $source);
    }
}
