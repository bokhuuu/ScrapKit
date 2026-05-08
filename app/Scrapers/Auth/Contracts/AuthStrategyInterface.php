<?php

declare(strict_types=1);

namespace App\Scrapers\Auth\Contracts;

/**
 * Contract for handling authentication on sites that require login.
 */
interface AuthStrategyInterface
{
    /**
     * Check if the current browser session is still logged in.
     */
    public function isAuthenticated(): bool;

    /**
     * Perform the login flow to authenticate the browser session.
     */
    public function authenticate(): void;

    /**
     * Refresh an expired session. Lighter than full re-authentication.
     */
    public function refresh(): void;
}
