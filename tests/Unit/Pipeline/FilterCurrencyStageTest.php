<?php

declare(strict_types=1);

use App\Scrapers\Exceptions\InvalidListingException;
use App\Scrapers\Pipeline\Stages\FilterCurrencyStage;

test('it passes through when currency is accepted', function () {
    $stage = new FilterCurrencyStage(['USD']);
    $dto = makeListing(['currency' => 'USD']);

    $result = $stage->handle($dto);

    expect($result->currency)->toBe('USD');
});

test('it throws when currency is not accepted', function () {
    $stage = new FilterCurrencyStage(['USD']);
    $dto = makeListing(['currency' => 'AMD']);

    expect(fn() => $stage->handle($dto))->toThrow(InvalidListingException::class);
});

test('it passes through when currency is null', function () {
    $stage = new FilterCurrencyStage(['USD']);
    $dto = makeListing(['currency' => null]);

    $result = $stage->handle($dto);

    expect($result->currency)->toBeNull();
});
