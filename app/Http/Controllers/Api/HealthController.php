<?php

declare(strict_types=1);

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Queue;
use Throwable;

/**
 * Reports the health status of all critical ScrapKit services.
 *
 * Used by Docker, uptime monitors and LaraKit's health dashboard.
 * Returns 503 if any service is unhealthy so monitors react automatically.
 */
class HealthController extends Controller
{
    /**
     * Check and return the health status of all services.
     *
     * Each service is checked independently - one failure does not
     * prevent the others from being checked and reported.
     */
    public function __invoke(): JsonResponse
    {
        $checks = [
            'database' => $this->checkDatabase(),
            'redis'    => $this->checkRedis(),
            'queue'    => $this->checkQueue(),
        ];

        $healthy = ! in_array('error', $checks, true);

        return response()->json([
            'status' => $healthy ? 'ok' : 'degraded',
            'checks' => $checks,
        ], $healthy ? 200 : 503);
    }

    /**
     * Verify the database connection is responsive.
     */
    private function checkDatabase(): string
    {
        try {
            DB::connection()->getPdo();

            return 'ok';
        } catch (Throwable) {
            return 'error';
        }
    }

    /**
     * Verify Redis is reachable via the cache layer.
     */
    private function checkRedis(): string
    {
        try {
            Cache::set('health_check', true, 5);

            return 'ok';
        } catch (Throwable) {
            return 'error';
        }
    }

    /**
     * Verify the queue driver is configured and reachable.
     */
    private function checkQueue(): string
    {
        try {
            Queue::size();

            return 'ok';
        } catch (Throwable) {
            return 'error';
        }
    }
}
