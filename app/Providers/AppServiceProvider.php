<?php

declare(strict_types=1);

namespace App\Providers;

use Illuminate\Support\ServiceProvider;
use Illuminate\Support\Facades\Event;
use App\Events\ScrapeCompleted;
use App\Events\ScrapeFailed;
use App\Listeners\SendScrapeCompletedNotification;
use App\Listeners\SendScrapeFailedNotification;

/**
 * Application service provider for bootstrapping services.
 */
class AppServiceProvider extends ServiceProvider
{
    /**
     * Register any application services.
     */
    public function register(): void {}

    /**
     * Bootstrap any application services.
     */
    public function boot(): void
    {
        Event::listen(ScrapeCompleted::class, SendScrapeCompletedNotification::class);
        Event::listen(ScrapeFailed::class, SendScrapeFailedNotification::class);
    }
}
