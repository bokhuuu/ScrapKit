<?php

declare(strict_types=1);

use App\Scrapers\Pipeline\Stages\NormalizeStringFieldsStage;

$stage = new NormalizeStringFieldsStage();

test('it trims whitespace from string fields', function () use ($stage) {
    $dto = makeListing(['district' => '  Kentron  ']);

    $result = $stage->handle($dto);

    expect($result->district)->toBe('Kentron');
});

test('it converts empty string to null', function () use ($stage) {
    $dto = makeListing(['district' => '   ']);

    $result = $stage->handle($dto);

    expect($result->district)->toBeNull();
});

test('it leaves null fields as null', function () use ($stage) {
    $dto = makeListing(['phone' => null]);

    $result = $stage->handle($dto);

    expect($result->phone)->toBeNull();
});
