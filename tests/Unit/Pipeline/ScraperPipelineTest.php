<?php

declare(strict_types=1);

use App\Scrapers\Exceptions\DuplicateListingException;
use App\Scrapers\Exceptions\InvalidListingException;
use App\Scrapers\Pipeline\PipelineStageInterface;
use App\Scrapers\Pipeline\ScraperPipeline;

test('it returns dto after passing through all stages', function () {
    $stage = Mockery::mock(PipelineStageInterface::class);
    $stage->shouldReceive('handle')->once()->andReturnArg(0);

    $pipeline = new ScraperPipeline([$stage]);
    $dto = makeListing();

    $result = $pipeline->process($dto);

    expect($result)->not->toBeNull()
        ->and($result->externalId)->toBe('test-123');
});

test('it returns null when InvalidListingException is thrown', function () {
    $stage = Mockery::mock(PipelineStageInterface::class);
    $stage->shouldReceive('handle')->andThrow(new InvalidListingException('bad data'));

    $pipeline = new ScraperPipeline([$stage]);

    $result = $pipeline->process(makeListing());

    expect($result)->toBeNull();
});

test('it returns null when DuplicateListingException is thrown', function () {
    $stage = Mockery::mock(PipelineStageInterface::class);
    $stage->shouldReceive('handle')->andThrow(new DuplicateListingException('duplicate'));

    $pipeline = new ScraperPipeline([$stage]);

    $result = $pipeline->process(makeListing());

    expect($result)->toBeNull();
});

test('it passes dto through multiple stages in order', function () {
    $first = Mockery::mock(PipelineStageInterface::class);
    $second = Mockery::mock(PipelineStageInterface::class);

    $first->shouldReceive('handle')->once()->andReturnArg(0);
    $second->shouldReceive('handle')->once()->andReturnArg(0);

    $pipeline = new ScraperPipeline([$first, $second]);

    $result = $pipeline->process(makeListing());

    expect($result)->not->toBeNull();
});
