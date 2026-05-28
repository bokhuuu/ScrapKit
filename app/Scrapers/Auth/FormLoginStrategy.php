<?php

declare(strict_types=1);

namespace App\Scrapers\Auth;

use Laravel\Dusk\Browser;
use RuntimeException;

/**
 * Handles form-based login for sites that require email + password authentication.
 *
 * Responsibilities:
 *   - Navigate to the login page
 *   - Fill credentials
 *   - Submit the form
 *   - Wait until a logged-in element confirms success
 *
 * No cookie management here - that belongs to CookieAuthStrategy.
 * This class only knows how to fill a form.
 */
class FormLoginStrategy
{
    public function __construct(
        private readonly Browser $browser,
        private readonly string $loginUrl,
        private readonly string $emailSelector,
        private readonly string $passwordSelector,
        private readonly string $submitSelector,
        private readonly string $successSelector,
        private readonly string $email,
        private readonly string $password,
        private readonly int $waitSeconds = 10,
    ) {}

    /**
     * Execute the full login flow.
     *
     * Throws RuntimeException if the success element never appears -
     * which means login failed (wrong credentials, site down, layout changed).
     */
    public function login(): void
    {
        $this->browser->visit($this->loginUrl);

        $this->browser->type($this->emailSelector, $this->email);
        $this->browser->type($this->passwordSelector, $this->password);
        $this->browser->click($this->submitSelector);

        try {
            $this->browser->waitFor($this->successSelector, $this->waitSeconds);
        } catch (\Throwable $e) {
            throw new RuntimeException(
                "Login failed - success selector [{$this->successSelector}] never appeared after submitting form.",
                previous: $e,
            );
        }
    }
}
