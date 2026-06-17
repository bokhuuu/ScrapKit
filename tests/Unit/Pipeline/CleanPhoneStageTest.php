<?php

declare(strict_types=1);

use App\Scrapers\Pipeline\Stages\CleanPhoneStage;

$stage = new CleanPhoneStage;

test('it strips non-numeric characters from phone', function () use ($stage) {
    $dto = makeListing(['phone' => '+374 (99) 123-456']);

    $result = $stage->handle($dto);

    expect($result->phone)->toBe('37499123456');
});

test('it returns null when phone is null', function () use ($stage) {
    $dto = makeListing(['phone' => null]);

    $result = $stage->handle($dto);

    expect($result->phone)->toBeNull();
});

test('it returns null when phone has no digits', function () use ($stage) {
    $dto = makeListing(['phone' => '+++---']);

    $result = $stage->handle($dto);

    expect($result->phone)->toBeNull();
});
