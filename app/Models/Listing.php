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
        'living_area',
        'kitchen_area',

        'rooms',
        'floor',
        'total_floors',
        'year_built',
        'ceiling_height',
        'building_type',
        'condition',
        'is_new_building',

        'has_balcony',
        'has_furniture',
        'has_elevator',
        'has_parking',
        'has_garage',

        'country',
        'city',
        'district',
        'address',
        'latitude',
        'longitude',

        'phone',
        'description',
        'images',

        'listing_date',
        'scraped_at',
    ];

    protected $casts = [
        'price'        => 'float',
        'price_per_sqm' => 'float',
        'area'         => 'float',
        'living_area'  => 'float',
        'kitchen_area' => 'float',
        'ceiling_height' => 'float',
        'latitude'     => 'float',
        'longitude'    => 'float',
        'is_new_building' => 'boolean',
        'has_balcony'  => 'boolean',
        'has_furniture' => 'boolean',
        'has_elevator' => 'boolean',
        'has_parking'  => 'boolean',
        'has_garage'   => 'boolean',
        'images'       => 'array',
        'listing_date' => 'datetime',
        'scraped_at'   => 'datetime',
    ];

    /**
     * Scope to filter listings by scraping source.
     */
    public function scopeForSource(Builder $query, string $source): Builder
    {
        return $query->where('source_profile_name', $source);
    }
}
