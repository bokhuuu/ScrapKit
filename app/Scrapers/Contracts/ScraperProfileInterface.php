<?php

declare(strict_types=1);

namespace App\Scrapers\Contracts;

use App\Scrapers\Auth\Contracts\AuthStrategyInterface;

interface ScraperProfileInterface
{
    public function getName(): string;
    public function getBaseUrl(): string;
    public function getIndexUrlPattern(): string;
    public function getMaxPages(): int;
    public function getIndexSelectors(): array;
    public function getDetailSelectors(): array;
    public function getPipelineStages(): array;
    public function getExports(): array;
    public function getAuthStrategy(): ?AuthStrategyInterface;
    public function getRequestDelay(): int;
    public function getBrowserConfig(): array;
}
