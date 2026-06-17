<?php

declare(strict_types=1);

use App\Scrapers\Exceptions\InvalidListingException;
use App\Scrapers\Pipeline\Stages\ValidateRequiredFieldsStage;

test('it passes through when all required fields are present', function () {
    $stage = new ValidateRequiredFieldsStage(['externalId', 'url', 'price']);
    $dto = makeListing();

    $result = $stage->handle($dto);

    expect($result->externalId)->toBe('test-123');
});

test('it throws when a required field is null', function () {
    $stage = new ValidateRequiredFieldsStage(['price']);
    $dto = makeListing(['price' => null]);

    expect(fn () => $stage->handle($dto))->toThrow(InvalidListingException::class);
});

test('it throws when a required field is empty string', function () {
    $stage = new ValidateRequiredFieldsStage(['district']);
    $dto = makeListing(['district' => '']);

    expect(fn () => $stage->handle($dto))->toThrow(InvalidListingException::class);
});
