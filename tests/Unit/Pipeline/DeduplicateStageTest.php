<?php

declare(strict_types=1);

use App\Repositories\ListingRepository;
use App\Scrapers\Exceptions\DuplicateListingException;
use App\Scrapers\Pipeline\Stages\DeduplicateStage;

test('it passes through when listing does not exist', function () {
    $repository = Mockery::mock(ListingRepository::class);
    $repository->shouldReceive('existsByExternalId')
        ->with('test-123', 'listam')
        ->andReturn(false);

    $stage = new DeduplicateStage($repository);
    $dto = makeListing();

    $result = $stage->handle($dto);

    expect($result->externalId)->toBe('test-123');
});

test('it throws when listing already exists', function () {
    $repository = Mockery::mock(ListingRepository::class);
    $repository->shouldReceive('existsByExternalId')
        ->with('test-123', 'listam')
        ->andReturn(true);

    $stage = new DeduplicateStage($repository);
    $dto = makeListing();

    expect(fn () => $stage->handle($dto))->toThrow(DuplicateListingException::class);
});
