<?php

declare(strict_types=1);

namespace App\Scrapers\Browser;

use Illuminate\Support\Facades\Redis;

/**
 * Resolves the next proxy address to use for a scraper request.
 *
 * Supports two strategies:
 *   - round_robin: cycles through proxies in order using a Redis counter.
 *   - random: picks a proxy at random from the list.
 *
 * Returns null when proxies are disabled or the list is empty,
 * so callers can safely use it without checking config themselves.
 */
class ProxyResolver
{
    private const COUNTER_KEY = 'scraper:proxy_counter';

    /**
     * Resolve the next proxy to use.
     *
     * Returns a full proxy URL string or null if proxying is disabled.
     */
    public function resolve(): ?string
    {
        if (! config('scraper.proxy_enabled')) {
            return null;
        }

        $proxies = config('scraper.proxies');

        if (empty($proxies)) {
            return null;
        }

        return config('scraper.proxy_strategy') === 'round_robin'
            ? $this->roundRobin($proxies)
            : $this->random($proxies);
    }

    /**
     * Cycle through proxies in order.
     *
     * Uses Redis INCR to atomically increment a counter across all workers.
     * Modulo ensures the index wraps around when the end of the list is reached.
     */
    private function roundRobin(array $proxies): string
    {
        $index = Redis::incr(self::COUNTER_KEY) % count($proxies);

        return $proxies[$index];
    }

    /**
     * Pick a proxy at random from the list.
     */
    private function random(array $proxies): string
    {
        return $proxies[array_rand($proxies)];
    }

    /**
     * Format proxy with credentials if configured.
     *
     * Call this before passing the proxy to ChromeDriver.
     * Returns the proxy unchanged if no credentials are set.
     */
    public function withCredentials(string $proxy): string
    {
        $username = config('scraper.proxy_username');
        $password = config('scraper.proxy_password');

        if (! $username || ! $password) {
            return $proxy;
        }

        $parsed = parse_url($proxy);

        return sprintf(
            '%s://%s:%s@%s:%s',
            $parsed['scheme'] ?? 'http',
            $username,
            $password,
            $parsed['host'],
            $parsed['port'] ?? 8080,
        );
    }
}
