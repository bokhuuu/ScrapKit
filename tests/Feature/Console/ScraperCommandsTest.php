<?php

declare(strict_types=1);

use App\Enums\ScraperState;
use App\Models\ScraperRun;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Bus;

uses(RefreshDatabase::class);

test('scraper:run fails with unknown source', function () {
    $this->artisan('scraper:run', ['source' => 'unknown'])
        ->assertFailed()
        ->expectsOutputToContain('Unknown source');
});

test('scraper:run succeeds with valid source', function () {
    Bus::fake();

    $this->artisan('scraper:run', ['source' => 'listam'])
        ->assertSuccessful()
        ->expectsOutputToContain('Starting scrape run for [listam]');

    expect(ScraperRun::count())->toBe(1);
});

test('scraper:status warns when no runs exist', function () {
    $this->artisan('scraper:status', ['source' => 'listam'])
        ->assertSuccessful()
        ->expectsOutputToContain('No runs found');
});

test('scraper:status shows latest run', function () {
    ScraperRun::create([
        'source'     => 'listam',
        'state'      => ScraperState::Completed,
        'started_at' => now(),
    ]);

    $this->artisan('scraper:status', ['source' => 'listam'])
        ->assertSuccessful();
});

test('scraper:cancel fails when run not found', function () {
    $this->artisan('scraper:cancel', ['run_id' => 999])
        ->assertFailed()
        ->expectsOutputToContain('No scraper run found');
});

test('scraper:cancel warns when run is already terminal', function () {
    $run = ScraperRun::create([
        'source'      => 'listam',
        'state'       => ScraperState::Completed,
        'started_at'  => now(),
        'finished_at' => now(),
    ]);

    $this->artisan('scraper:cancel', ['run_id' => $run->id])
        ->assertSuccessful()
        ->expectsOutputToContain('already');
});

test('scraper:cancel cancels an active run', function () {
    Bus::fake();

    $run = ScraperRun::create([
        'source'     => 'listam',
        'state'      => ScraperState::Running,
        'started_at' => now(),
    ]);

    $this->artisan('scraper:cancel', ['run_id' => $run->id])
        ->assertSuccessful()
        ->expectsOutputToContain('cancelled');

    expect(ScraperRun::find($run->id)->state)->toBe(ScraperState::Cancelled);
});
