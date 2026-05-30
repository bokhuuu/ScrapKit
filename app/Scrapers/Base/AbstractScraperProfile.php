<?php

declare(strict_types=1);

namespace App\Scrapers\Base;

use App\Repositories\ListingRepository;
use App\Scrapers\Auth\Contracts\AuthStrategyInterface;
use App\Scrapers\Contracts\ScraperProfileInterface;
use App\Scrapers\Pipeline\Stages\CalculatePricePerSqmStage;
use App\Scrapers\Pipeline\Stages\CleanAreaStage;
use App\Scrapers\Pipeline\Stages\CleanBuildingTypeStage;
use App\Scrapers\Pipeline\Stages\CleanPhoneStage;
use App\Scrapers\Pipeline\Stages\CleanPriceStage;
use App\Scrapers\Pipeline\Stages\DeduplicateStage;
use App\Scrapers\Pipeline\Stages\NormalizeStringFieldsStage;
use App\Scrapers\Pipeline\Stages\ValidateRequiredFieldsStage;

/**
 * Default implementations for methods common across all scraper profiles.
 * Concrete profiles extend this and override only what differs.
 * 
 * Abstract methods must be implemented by every profile - they contain
 * site-specific data that cannot have a sensible default.
 */
abstract class AbstractScraperProfile implements ScraperProfileInterface
{
    /**
     * Fields required for a listing to be processable.
     * Passed to ValidateRequiredFieldsStage at pipeline construction.
     * Different sites expose different data - price may not always be available.
     */
    abstract public function getRequiredFields(): array;

    /**
     * Known districts for the target city.
     * Override in profiles that use EnrichDistrictStage.
     * Returns empty array by default - most sites do not need district enrichment.
     */
    public function getDistricts(): array
    {
        return [];
    }

    /**
     * Colloquial district name aliases for the target city.
     * Override in profiles where the site uses non-standard district names.
     * Returns empty array by default - most sites use official names only.
     */
    public function getDistrictAliases(): array
    {
        return [];
    }

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
     * Maximum pages to scrape per run.
     * Set SCRAPER_MAX_PAGES=2 locally for fast testing.
     */
    public function getMaxPages(): int
    {
        return (int) config('scraper.default_max_pages');
    }

    /**
     * Standard desktop browser configuration.
     * 1920x1080 ensures sites render their full desktop layout.
     * User agent mimics real Chrome to avoid bot detection.
     */
    public function getBrowserConfig(): array
    {
        return [
            'headless' => true,
            'window_size' => config('scraper.browser_window_size'),
            'user_agent'  => config('scraper.user_agent'),
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
            new NormalizeStringFieldsStage(),
            new ValidateRequiredFieldsStage($this->getRequiredFields()),
            new CleanPriceStage(),
            new CleanPhoneStage(),
            new CleanAreaStage(),
            new CalculatePricePerSqmStage(),
            new CleanBuildingTypeStage(),
            new DeduplicateStage(new ListingRepository()),

        ];
    }

    /**
     * Telegram is the default notification channel.
     * Override to add Slack, disable notifications, or use multiple channels.
     * Return an empty array to silence all notifications for a profile.
     */
    public function getNotifiers(): array
    {
        return ['telegram'];
    }

    /**
     * Default queue settings read from config.
     * Override for slow or rate-limited sites that need different concurrency or timeouts.
     */
    public function getQueueConfig(): array
    {
        return [
            'concurrency' => (int) config('scraper.default_concurrency'),
            'retry_times' => (int) config('scraper.default_retry_times'),
            'timeout'     => (int) config('scraper.default_timeout_s'),
        ];
    }

    /**
     * Build a paginated index URL for the given page number.
     *
     * Replaces the {page} placeholder in getIndexUrlPattern().
     */
    public function buildIndexUrl(int $page): string
    {
        return str_replace('{page}', (string) $page, $this->getIndexUrlPattern());
    }
}
