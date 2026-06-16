<?php

declare(strict_types=1);

use Illuminate\Support\Facades\Route;

Route::middleware([
    'auth:sanctum',
    'throttle:api.per_token',
])->group(function (): void {});
