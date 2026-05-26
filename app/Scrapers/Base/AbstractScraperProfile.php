<?php

declare(strict_types=1);

namespace App\Scrapers\Base;

use App\Scrapers\Auth\Contracts\AuthStrategyInterface;
use App\Scrapers\Contracts\ScraperProfileInterface;

/**
 * Default implementations for methods common across all scraper profiles.
 * Concrete profiles extend this and override only what differs.
 */
abstract class AbstractScraperProfile implements ScraperProfileInterface
{
    /**
     * Most sites are public - no auth required by default.
     * Override in profiles that require login (e.g. to reveal phone numbers).
     */
    public function getAuthStrategy(): ?AuthStrategyInterface
    {
        return null;
    }

    /**
     * Default delay between requests in seconds, read from config.
     * Override in site profiles that need slower or faster pacing.
     */
    public function getRequestDelay(): int
    {
        return (int) config('scraper.default_request_delay_s');
    }

    /**
     * Standard desktop browser configuration.
     * 1920x1080 ensures sites render their full desktop layout.
     * User agent mimics real Chrome to avoid bot detection.
     */
    public function getBrowserConfig(): array
    {
        return [
            'headless'    => true,
            'window_size' => '1920,1080',
            'user_agent'  => 'Mozilla/5.0 (X11; Linux x86_64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/122.0.0.0 Safari/537.36',
        ];
    }

    /**
     * Excel for client deliverables, JSON for API/integration use.
     * Override to change export formats per project.
     */
    public function getExports(): array
    {
        return ['excel', 'json'];
    }

    /**
     * Default pipeline handles normalization, validation,
     * price/area cleaning and deduplication.
     * Override to add site-specific stages (e.g. CleanMileageStage for MyAuto).
     */
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
