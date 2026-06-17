<?php

declare(strict_types=1);

use App\DTOs\ListingDTO;
use Tests\DuskTestCase;
use Tests\TestCase;

/*
|--------------------------------------------------------------------------
| Test Case
|--------------------------------------------------------------------------
|
| The closure you provide to your test functions is always bound to a specific PHPUnit test
| case class. By default, that class is "PHPUnit\Framework\TestCase". Of course, you may
| need to change it using the "pest()" function to bind a different classes or traits.
|
*/

pest()->extend(DuskTestCase::class)
    ->in('Browser');

pest()->extend(TestCase::class)
    // ->use(Illuminate\Foundation\Testing\RefreshDatabase::class)
    ->in('Feature', 'Unit');

/*
|--------------------------------------------------------------------------
| Expectations
|--------------------------------------------------------------------------
|
| When you're writing tests, you often need to check that values meet certain conditions. The
| "expect()" function gives you access to a set of "expectations" methods that you can use
| to assert different things. Of course, you may extend the Expectation API at any time.
|
*/

expect()->extend('toBeOne', function () {
    return $this->toBe(1);
});

/*
|--------------------------------------------------------------------------
| Functions
|--------------------------------------------------------------------------
|
| While Pest is very powerful out-of-the-box, you may have some testing code specific to your
| project that you don't want to repeat in every file. Here you can also expose helpers as
| global functions to help you to reduce the number of lines of code in your test files.
|
*/

function makeListing(array $overrides = []): ListingDTO
{
    return ListingDTO::fromArray(array_merge([
        'external_id' => 'test-123',
        'url' => 'https://list.am/en/item/123',
        'source_profile_name' => 'listam',
        'listing_type' => 'sale',
        'property_type' => 'apartment',
        'price' => 150000,
        'currency' => 'USD',
        'price_per_sqm' => null,
        'area' => 75.0,
        'rooms' => 3,
        'bathrooms' => null,
        'floor' => 4,
        'total_floors' => 9,
        'ceiling_height' => null,
        'building_type' => 'Panel',
        'condition' => 'Good',
        'is_new_building' => false,
        'district' => 'Kentron',
        'address' => 'Mashtots Ave 15',
        'phone' => null,
        'agency_name' => null,
        'image_urls' => [],
        'extras' => [],
        'listing_date' => '2024-01-15 10:00:00',
        'scraped_at' => '2024-01-15 12:00:00',
    ], $overrides));
}
