<?php

declare(strict_types=1);

use App\Models\User;
use App\Repositories\ListingRepository;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Laravel\Sanctum\Sanctum;

uses(RefreshDatabase::class);

beforeEach(function () {
    Sanctum::actingAs(User::factory()->create());
});

test('listings endpoint returns paginated results', function () {
    $repository = new ListingRepository();
    $repository->updateOrCreate(makeListing()->toArray());

    $this->getJson('/api/listings?source=listam')
        ->assertOk()
        ->assertJsonStructure(['data', 'total', 'per_page']);
});

test('listings endpoint filters by district', function () {
    $repository = new ListingRepository();
    $repository->updateOrCreate(makeListing(['district' => 'Kentron'])->toArray());
    $repository->updateOrCreate(makeListing([
        'external_id' => 'test-456',
        'url'         => 'https://list.am/en/item/456',
        'district'    => 'Arabkir',
    ])->toArray());

    $response = $this->getJson('/api/listings?source=listam&district=Kentron')
        ->assertOk();

    expect($response->json('total'))->toBe(1);
});

test('stats endpoint returns district price data', function () {
    $this->getJson('/api/listings/stats?source=listam')
        ->assertOk()
        ->assertJsonStructure(['source', 'data']);
});
