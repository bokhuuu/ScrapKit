<?php

declare(strict_types=1);

use App\Scrapers\Pipeline\Stages\CleanBuildingTypeStage;

$stage = new CleanBuildingTypeStage;

test('it lowercases the building type', function () use ($stage) {
    $dto = makeListing(['building_type' => 'Panel']);

    $result = $stage->handle($dto);

    expect($result->buildingType)->toBe('panel');
});

test('it collapses multiple spaces into one', function () use ($stage) {
    $dto = makeListing(['building_type' => 'Monolithic  Frame']);

    $result = $stage->handle($dto);

    expect($result->buildingType)->toBe('monolithic frame');
});

test('it returns null when building type is null', function () use ($stage) {
    $dto = makeListing(['building_type' => null]);

    $result = $stage->handle($dto);

    expect($result->buildingType)->toBeNull();
});
