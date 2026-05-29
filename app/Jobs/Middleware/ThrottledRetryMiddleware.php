<?php

declare(strict_types=1);

namespace App\Jobs\Middleware;

use Closure;

/**
 * Adds an backoff delay between job retries.
 *
 * On first failure waits 30s, second 60s, third 90s.
 * Prevents hammering a site that is temporarily blocking requests.
 */
class ThrottledRetryMiddleware
{
    public function handle(mixed $job, Closure $next): void
    {
        try {
            $next($job);
        } catch (\Throwable $e) {
            if ($job->attempts() >= $job->tries) {
                $job->fail($e);
                return;
            }

            $job->release(
                $job->attempts() * 30
            );
        }
    }
}
