<?php

declare(strict_types=1);

namespace App\Scrapers\Auth;

use App\Scrapers\Auth\Contracts\AuthStrategyInterface;
use Laravel\Dusk\Browser;

/**
 * Cookie-based authentication strategy with form login fallback.
 *
 * Flow on authenticate():
 *   1. Check if a cookie file exists for this profile
 *   2. If yes - inject cookies into the browser session and reload
 *   3. Check if the session is actually valid (authCheckSelector present)
 *   4. If cookies were stale or missing - run FormLoginStrategy
 *   5. Save the fresh cookies to disk for next time
 *
 * This means the first run logs in via form, every subsequent run
 * restores the session from disk without touching the login page.
 *
 * Cookie storage: storage/app/scraper/cookies/{profileName}.json
 */
class CookieAuthStrategy implements AuthStrategyInterface
{
    public function __construct(
        private readonly Browser $browser,
        private readonly string $profileName,
        private readonly string $baseUrl,
        private readonly string $authCheckSelector,
        private readonly FormLoginStrategy $loginStrategy,
    ) {}

    /**
     * Check if the browser session is currently authenticated.
     *
     * We look for a DOM element that only appears when logged in
     * (e.g. a user menu, avatar, or account link).
     * If it's present - we are logged in.
     */
    public function isAuthenticated(): bool
    {
        return $this->browser->element($this->authCheckSelector) !== null;
    }

    /**
     * Authenticate the browser session.
     *
     * Tries cookie restoration first - much faster than form login.
     * Falls back to form login if cookies are missing or expired.
     * Always saves fresh cookies after successful form login.
     */
    public function authenticate(): void
    {
        if ($this->tryRestoreFromCookies()) {
            return;
        }

        $this->loginStrategy->login();
        $this->saveCookies();
    }

    /**
     * Refresh an expired session.
     *
     * Delegates to authenticate() - the same cookie-first logic applies.
     * If cookies are still valid this is nearly instant.
     * If expired, a new form login runs and fresh cookies are saved.
     */
    public function refresh(): void
    {
        $this->authenticate();
    }

    /**
     * Attempt to restore the session from a saved cookie file.
     *
     * Returns true if cookies were loaded AND the session is authenticated.
     * Returns false if the file is missing, empty, or cookies are expired.
     */
    private function tryRestoreFromCookies(): bool
    {
        $path = $this->cookiePath();

        if (! file_exists($path)) {
            return false;
        }

        $cookies = json_decode(file_get_contents($path), true);

        if (empty($cookies)) {
            return false;
        }

        // Cookies can only be set for the domain the browser is currently on.
        // Navigate to the base URL first so the domain matches.
        $this->browser->visit($this->baseUrl);

        foreach ($cookies as $cookie) {
            if (empty($cookie['name']) || ! array_key_exists('value', $cookie)) {
                continue;
            }

            $this->browser->driver->manage()->addCookie($cookie);
        }

        // Reload with cookies active so the server recognizes the session
        $this->browser->refresh();

        return $this->isAuthenticated();
    }

    /**
     * Dump the current browser cookies to disk.
     *
     * Called immediately after a successful form login.
     * All cookies are stored - the session cookie is among them.
     */
    private function saveCookies(): void
    {
        $path = $this->cookiePath();
        $dir  = dirname($path);

        if (! is_dir($dir)) {
            mkdir($dir, 0755, recursive: true);
        }

        $cookies = $this->browser->driver->manage()->getCookies();

        file_put_contents(
            $path,
            json_encode($cookies, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE),
        );
    }

    /**
     * Resolve the cookie file path for this profile.
     *
     * Convention: storage/app/scraper/cookies/{profileName}.json
     * Each site has its own file - no cross-contamination.
     */
    private function cookiePath(): string
    {
        return storage_path("app/scraper/cookies/{$this->profileName}.json");
    }
}
