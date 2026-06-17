<?php

declare(strict_types=1);

namespace App\Providers;

use App\Events\ScrapeCompleted;
use App\Events\ScrapeFailed;
use App\Listeners\SendScrapeCompletedNotification;
use App\Listeners\SendScrapeFailedNotification;
use App\Listeners\TriggerScrapeExport;
use App\Scrapers\Browser\BrowserPool;
use App\Scrapers\Browser\ProxyResolver;
use Illuminate\Cache\RateLimiting\Limit;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Event;
use Illuminate\Support\Facades\RateLimiter;
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

        $this->app->bind(ProxyResolver::class, fn () => new ProxyResolver);
    }

    /**
     * Bootstrap any application services.
     */
    public function boot(): void
    {
        /**
         * Global API rate limiter - caps total requests per minute across all clients.
         * Protects the server regardless of how many tokens exist.
         */
        RateLimiter::for('api.global', function () {
            return Limit::perMinute(config('scraper.api_rate_limit', 60));
        });
        /**
         * Per-token rate limiter - caps requests per minute per Sanctum token.
         * Ensures no single client can starve others. Falls back to IP if unauthenticated.
         */
        RateLimiter::for('api.per_token', function (Request $request) {
            return Limit::perMinute(config('scraper.api_per_token_limit', 30))
                ->by($request->user()?->id ?: $request->ip());
        });

        Event::listen(ScrapeCompleted::class, SendScrapeCompletedNotification::class);
        Event::listen(ScrapeFailed::class, SendScrapeFailedNotification::class);
        Event::listen(ScrapeCompleted::class, TriggerScrapeExport::class);
    }
}
