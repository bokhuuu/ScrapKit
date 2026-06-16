<?php

declare(strict_types=1);

use App\Scrapers\Pipeline\Stages\EnrichDistrictStage;

$districts = ['Kentron', 'Arabkir', 'Shengavit'];

test('it keeps district if already set', function () use ($districts) {
    $stage = new EnrichDistrictStage($districts);
    $dto = makeListing(['district' => 'Kentron', 'address' => 'Some street in Arabkir']);

    $result = $stage->handle($dto);

    expect($result->district)->toBe('Kentron');
});

test('it extracts district from address when district is null', function () use ($districts) {
    $stage = new EnrichDistrictStage($districts);
    $dto = makeListing(['district' => null, 'address' => 'Mashtots Ave, Kentron']);

    $result = $stage->handle($dto);

    expect($result->district)->toBe('Kentron');
});

test('it returns null when no known district found in address', function () use ($districts) {
    $stage = new EnrichDistrictStage($districts);
    $dto = makeListing(['district' => null, 'address' => 'Some unknown street']);

    $result = $stage->handle($dto);

    expect($result->district)->toBeNull();
});

test('it returns null when both district and address are null', function () use ($districts) {
    $stage = new EnrichDistrictStage($districts);
    $dto = makeListing(['district' => null, 'address' => null]);

    $result = $stage->handle($dto);

    expect($result->district)->toBeNull();
});
