<?php

declare(strict_types=1);

namespace App\Scrapers\Base;

use App\Scrapers\Auth\Contracts\AuthStrategyInterface;
use App\Scrapers\Contracts\ScraperProfileInterface;

abstract class AbstractScraperProfile implements ScraperProfileInterface
{
    public function getAuthStrategy(): ?AuthStrategyInterface
    {
        return null;
    }

    public function getRequestDelay(): int
    {
        return 2;
    }

    public function getBrowserConfig(): array
    {
        return [
            'headless'    => true,
            'window_size' => '1920,1080',
            'user_agent'  => 'Mozilla/5.0 (X11; Linux x86_64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/122.0.0.0 Safari/537.36',
        ];
    }

    public function getExports(): array
    {
        return ['excel', 'json'];
    }

    public function getPipelineStages(): array
    {
        return [
            \App\Scrapers\Pipeline\Stages\NormalizeFieldsStage::class,
            \App\Scrapers\Pipeline\Stages\ValidateRequiredFieldsStage::class,
            \App\Scrapers\Pipeline\Stages\CleanPriceStage::class,
            \App\Scrapers\Pipeline\Stages\CleanAreaStage::class,
            \App\Scrapers\Pipeline\Stages\DeduplicateStage::class,
        ];
    }
}
