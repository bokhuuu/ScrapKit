<?php

declare(strict_types=1);

use App\Models\Listing;
use App\Repositories\ListingRepository;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

$repository = new ListingRepository();

function listingData(array $overrides = []): array
{
    return array_merge([
        'external_id'         => 'test-123',
        'source_profile_name' => 'listam',
        'url'                 => 'https://list.am/en/item/123',
        'listing_type'        => 'sale',
        'property_type'       => 'apartment',
        'price'               => 150000.0,
        'currency'            => 'USD',
        'price_per_sqm'       => 2000.0,
        'area'                => 75.0,
        'images'              => [],
        'extras'              => [],
        'scraped_at'          => now()->toDateTimeString(),
    ], $overrides);
}

test('it saves a listing to the database', function () use ($repository) {
    $repository->updateOrCreate(listingData());

    expect(Listing::count())->toBe(1);
});

test('it updates an existing listing instead of creating a duplicate', function () use ($repository) {
    $repository->updateOrCreate(listingData(['price' => 150000.0]));
    $repository->updateOrCreate(listingData(['price' => 160000.0]));

    expect(Listing::count())->toBe(1);
    expect(Listing::first()->price)->toBe(160000.0);
});

test('it returns true when listing exists by external id', function () use ($repository) {
    $repository->updateOrCreate(listingData());

    expect($repository->existsByExternalId('test-123', 'listam'))->toBeTrue();
});

test('it returns false when listing does not exist', function () use ($repository) {
    expect($repository->existsByExternalId('listam', 'nonexistent'))->toBeFalse();
});

test('it finds listings by source', function () use ($repository) {
    $repository->updateOrCreate(listingData());
    $repository->updateOrCreate(listingData([
        'external_id' => 'test-456',
        'url'         => 'https://list.am/en/item/456',
    ]));

    $results = $repository->findBySource('listam');

    expect($results)->toHaveCount(2);
});

test('it counts listings by source', function () use ($repository) {
    $repository->updateOrCreate(listingData());

    expect($repository->countBySource('listam'))->toBe(1);
});
