<?php

declare(strict_types=1);

use App\Scrapers\Pipeline\Stages\CleanPriceStage;

$stage = new CleanPriceStage;

test('it cleans a formatted price string to a float', function () use ($stage) {
    $dto = makeListing(['price' => 150000]);

    $result = $stage->handle($dto);

    expect($result->price)->toBe(150000.0);
});

test('it returns null when price is null', function () use ($stage) {
    $dto = makeListing(['price' => null]);

    $result = $stage->handle($dto);

    expect($result->price)->toBeNull();
});

test('it returns null when price is zero', function () use ($stage) {
    $dto = makeListing(['price' => 0]);

    $result = $stage->handle($dto);

    expect($result->price)->toBeNull();
});
