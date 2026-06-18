<?php

declare(strict_types=1);

namespace App\Jobs\Middleware;

use Closure;

/**
 * Adds a backoff delay between job retries.
 *
 * On first failure waits 30s, second 60s, third 90s.
 * Prevents hammering a site that is temporarily blocking requests.
 */
class ThrottledRetryMiddleware
{
    /**
     * Lets the job run, and if it fails, either releases it with an
     * increasing delay or marks it permanently failed once retries
     * are exhausted.
     */
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
                $job->attempts() * config('scraper.retry_base_delay_s')
            );
        }
    }
}
