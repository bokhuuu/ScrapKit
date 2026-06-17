<?php

declare(strict_types=1);

use App\Enums\ScraperState;
use App\Models\ScraperRun;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Bus;
use Laravel\Sanctum\Sanctum;

uses(RefreshDatabase::class);

beforeEach(function () {
    Sanctum::actingAs(User::factory()->create());
});

test('start returns 422 with invalid source', function () {
    Bus::fake();

    $this->postJson('/api/scrape/start', ['source' => 'unknown'])
        ->assertUnprocessable();
});

test('start returns 202 with valid source', function () {
    Bus::fake();

    $this->postJson('/api/scrape/start', ['source' => 'listam'])
        ->assertStatus(202)
        ->assertJsonStructure(['message', 'run_id', 'source']);
});

test('status returns 404 when no runs exist', function () {
    $this->getJson('/api/scrape/status?source=listam')
        ->assertNotFound();
});

test('status returns latest run', function () {
    ScraperRun::create([
        'source' => 'listam',
        'state' => ScraperState::Running,
        'started_at' => now(),
    ]);

    $this->getJson('/api/scrape/status?source=listam')
        ->assertOk()
        ->assertJsonStructure(['run_id', 'source', 'state']);
});

test('cancel returns success for active run', function () {
    $run = ScraperRun::create([
        'source' => 'listam',
        'state' => ScraperState::Running,
        'started_at' => now(),
    ]);

    $this->postJson('/api/scrape/cancel', ['run_id' => $run->id])
        ->assertOk()
        ->assertJsonPath('run_id', $run->id);
});
