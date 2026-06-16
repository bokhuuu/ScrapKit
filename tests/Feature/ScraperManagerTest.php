<?php

declare(strict_types=1);

use App\Enums\ScraperState;
use App\Models\ScraperRun;
use App\Repositories\ScraperRunRepository;
use App\Scrapers\Contracts\ScraperProfileInterface;
use App\Scrapers\ScraperManager;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Bus;

uses(RefreshDatabase::class);

function makeProfile(int $pages = 2): ScraperProfileInterface
{
    $profile = Mockery::mock(ScraperProfileInterface::class);

    $profile->shouldReceive('getName')->andReturn('listam');
    $profile->shouldReceive('getMaxPages')->andReturn($pages);
    $profile->shouldReceive('getQueueConfig')->andReturn([]);
    $profile->shouldReceive('buildIndexUrl')->andReturnUsing(
        fn(int $page) => "https://list.am/en/category/60/{$page}"
    );

    return $profile;
}

test('it creates a scraper run record when started', function () {
    Bus::fake();

    $manager = new ScraperManager(new ScraperRunRepository());
    $manager->run(makeProfile());

    expect(ScraperRun::count())->toBe(1);
    expect(ScraperRun::first()->state)->toBe(ScraperState::Running);
    expect(ScraperRun::first()->source)->toBe('listam');
});

test('it dispatches one crawl index job per page', function () {
    Bus::fake();

    $manager = new ScraperManager(new ScraperRunRepository());
    $manager->run(makeProfile(pages: 3));

    Bus::assertBatchCount(1);
});

test('it marks a run as cancelled', function () {
    Bus::fake();

    $manager = new ScraperManager(new ScraperRunRepository());
    $manager->run(makeProfile());

    $run = ScraperRun::first();
    $manager->cancel($run->id);

    expect(ScraperRun::find($run->id)->state)->toBe(ScraperState::Cancelled);
});
