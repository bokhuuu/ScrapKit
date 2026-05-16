<?php

declare(strict_types=1);

namespace App\Repositories;

use App\Models\Listing;
use Illuminate\Support\Collection;

class ListingRepository
{
    public function save(array $data): Listing
    {
        return Listing::create($data);
    }

    public function updateOrCreate(array $data): Listing
    {
        return Listing::updateOrCreate(
            ['external_id' => $data['external_id'], 'source' => $data['source']],
            $data
        );
    }

    public function existsByExternalId(string $externalId, string $source): bool
    {
        return Listing::where('external_id', $externalId)
            ->where('source', $source)
            ->exists();
    }

    public function findBySource(string $source): Collection
    {
        return Listing::forSource($source)->active()->get();
    }

    public function findByDistrict(string $district, string $source): Collection
    {
        return Listing::forSource($source)
            ->active()
            ->where('district', $district)
            ->orderBy('price')
            ->get();
    }

    public function countBySource(string $source): int
    {
        return Listing::where('source', $source)->count();
    }
}
