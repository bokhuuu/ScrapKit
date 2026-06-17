<?php

declare(strict_types=1);

use App\Http\Controllers\Api\HealthController;
use App\Http\Controllers\Api\ListingController;
use App\Http\Controllers\Api\ScrapeController;
use Illuminate\Support\Facades\Route;

Route::get('/health', HealthController::class);

Route::middleware([
    'auth:sanctum',
    'throttle:api.per_token',
])->group(function (): void {
    Route::post('/scrape/start', [ScrapeController::class, 'start']);
    Route::get('/scrape/status', [ScrapeController::class, 'status']);
    Route::post('/scrape/cancel', [ScrapeController::class, 'cancel']);

    Route::get('/listings', [ListingController::class, 'index']);
    Route::get('/listings/stats', [ListingController::class, 'stats']);
});
