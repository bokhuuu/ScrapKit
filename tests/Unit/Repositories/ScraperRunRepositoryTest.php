<?php

declare(strict_types=1);

use App\Enums\ScraperState;
use App\Models\ScraperRun;
use App\Repositories\ScraperRunRepository;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

$repository = new ScraperRunRepository();

function runData(array $overrides = []): array
{
    return array_merge([
        'source'     => 'listam',
        'state'      => ScraperState::Pending,
        'started_at' => now()->toDateTimeString(),
    ], $overrides);
}

test('it saves a scraper run', function () use ($repository) {
    $repository->save(runData());

    expect(ScraperRun::count())->toBe(1);
});

test('it updates state of a scraper run', function () use ($repository) {
    $run = $repository->save(runData());

    $repository->updateState($run->id, ScraperState::Running);

    expect(ScraperRun::find($run->id)->state)->toBe(ScraperState::Running);
});

test('it marks a run as completed', function () use ($repository) {
    $run = $repository->save(runData());

    $repository->markAsCompleted($run->id, savedListings: 100, scrapedPages: 5);

    $updated = ScraperRun::find($run->id);

    expect($updated->state)->toBe(ScraperState::Completed)
        ->and($updated->saved_listings)->toBe(100)
        ->and($updated->scraped_pages)->toBe(5)
        ->and($updated->finished_at)->not->toBeNull();
});

test('it marks a run as failed', function () use ($repository) {
    $run = $repository->save(runData());

    $repository->markAsFailed($run->id, 'Something went wrong');

    $updated = ScraperRun::find($run->id);

    expect($updated->state)->toBe(ScraperState::Failed)
        ->and($updated->error)->toBe('Something went wrong')
        ->and($updated->finished_at)->not->toBeNull();
});

test('it marks a run as cancelled', function () use ($repository) {
    $run = $repository->save(runData());

    $repository->markAsCancelled($run->id);

    expect(ScraperRun::find($run->id)->state)->toBe(ScraperState::Cancelled);
});

test('it finds a run by id', function () use ($repository) {
    $run = $repository->save(runData());

    $found = $repository->findById($run->id);

    expect($found->id)->toBe($run->id);
});

test('it finds the latest run for a source', function () use ($repository) {
    $repository->save(runData());
    $repository->save(runData());

    $latest = $repository->findLatest('listam');

    expect($latest)->not->toBeNull()
        ->and(ScraperRun::where('source', 'listam')->count())->toBe(2);
});
