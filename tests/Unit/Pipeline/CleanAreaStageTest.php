<?php

declare(strict_types=1);

use App\Scrapers\Pipeline\Stages\CleanAreaStage;

$stage = new CleanAreaStage;

test('it cleans a formatted area string to a float', function () use ($stage) {
    $dto = makeListing(['area' => '75.5 m²']);

    $result = $stage->handle($dto);

    expect($result->area)->toBe(75.5);
});

test('it normalizes comma decimal separator to period', function () use ($stage) {
    $dto = makeListing(['area' => '75,5']);

    $result = $stage->handle($dto);

    expect($result->area)->toBe(75.5);
});

test('it returns null when area is null', function () use ($stage) {
    $dto = makeListing(['area' => null]);

    $result = $stage->handle($dto);

    expect($result->area)->toBeNull();
});

test('it returns null when area is zero', function () use ($stage) {
    $dto = makeListing(['area' => '0']);

    $result = $stage->handle($dto);

    expect($result->area)->toBeNull();
});
