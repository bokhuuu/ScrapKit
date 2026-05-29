<?php

declare(strict_types=1);

namespace App\Jobs\Middleware;

use Closure;
use Illuminate\Support\Facades\Redis;

/**
 * Limits how many scraper jobs run concurrently per site.
 *
 * Uses Redis to track active job count per source.
 * If the limit is reached, the job is released back to the queue
 * and tried again after a short delay.
 */
class RateLimitedMiddleware
{
    public function __construct(
        private readonly string $source,
        private readonly int $maxConcurrent,
    ) {}

    public function handle(mixed $job, Closure $next): void
    {
        Redis::throttle("scraper:{$this->source}")
            ->allow($this->maxConcurrent)
            ->every(1)
            ->then(
                fn() => $next($job),
                function () use ($job) {
                    $job->release(5);
                }
            );
    }
}
