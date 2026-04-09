<?php

declare(strict_types=1);

namespace App\Scrapers\Auth\Contracts;

interface AuthStrategyInterface
{
    public function isAuthenticated(): bool;

    public function authenticate(): void;

    public function refresh(): void;
}
