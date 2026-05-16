<?php

declare(strict_types=1);

namespace App\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Casts\Attribute;
use Illuminate\Database\Eloquent\Model;

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

    protected function pricePerSqm(): Attribute
    {
        return Attribute::make(
            get: fn() => $this->area > 0 ? round($this->price / $this->area, 2) : null
        );
    }

    public function scopeActive(Builder $query): Builder
    {
        return $query->where('status', 'active');
    }

    public function scopeForSource(Builder $query, string $source): Builder
    {
        return $query->where('source', $source);
    }
}
