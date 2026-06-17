<?php

declare(strict_types=1);

use App\Scrapers\Pipeline\Stages\CalculatePricePerSqmStage;

$stage = new CalculatePricePerSqmStage;

test('it calculates price per sqm from price and area', function () use ($stage) {
    $dto = makeListing(['price' => 150000.0, 'area' => 75.0]);

    $result = $stage->handle($dto);

    expect($result->pricePerSqm)->toBe(2000.0);
});

test('it returns null price per sqm when price is null', function () use ($stage) {
    $dto = makeListing(['price' => null, 'area' => 75.0]);

    $result = $stage->handle($dto);

    expect($result->pricePerSqm)->toBeNull();
});

test('it returns null price per sqm when area is null', function () use ($stage) {
    $dto = makeListing(['price' => 150000.0, 'area' => null]);

    $result = $stage->handle($dto);

    expect($result->pricePerSqm)->toBeNull();
});

test('it returns null price per sqm when area is zero', function () use ($stage) {
    $dto = makeListing(['price' => 150000.0, 'area' => 0.0]);

    $result = $stage->handle($dto);

    expect($result->pricePerSqm)->toBeNull();
});
