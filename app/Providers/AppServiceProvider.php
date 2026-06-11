<?php

declare(strict_types=1);

namespace App\Providers;

use App\Events\ScrapeCompleted;
use App\Events\ScrapeFailed;
use App\Listeners\SendScrapeCompletedNotification;
use App\Listeners\SendScrapeFailedNotification;
use App\Listeners\TriggerScrapeExport;
use App\Scrapers\Browser\BrowserPool;
use Illuminate\Support\Facades\Event;
use Illuminate\Support\ServiceProvider;

/**
 * Application service provider for bootstrapping services.
 */
class AppServiceProvider extends ServiceProvider
{
    /**
     * Register any application services.
     */
    public function register(): void
    {
        $this->app->singleton(BrowserPool::class, function () {
            return new BrowserPool(
                size: config('scraper.browser_pool_size'),
            );
        });
    }

    /**
     * Bootstrap any application services.
     */
    public function boot(): void
    {
        Event::listen(ScrapeCompleted::class, SendScrapeCompletedNotification::class);
        Event::listen(ScrapeFailed::class, SendScrapeFailedNotification::class);
        Event::listen(ScrapeCompleted::class, TriggerScrapeExport::class);
    }
}
