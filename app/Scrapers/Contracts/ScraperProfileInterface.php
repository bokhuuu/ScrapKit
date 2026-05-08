<?php

declare(strict_types=1);

namespace App\Scrapers\Contracts;

use App\Scrapers\Auth\Contracts\AuthStrategyInterface;

/**
 * Contract every scraper profile must implement.
 * One profile = one target site.
 */
interface ScraperProfileInterface
{
    /**
     * Unique identifier for this profile. Used in logs, file names, commands.
     */
    public function getName(): string;

    /**
     * Root domain of the site. Used to build full URLs from relative links.
     */
    public function getBaseUrl(): string;

    /**
     * URL pattern for paginated listing pages. {page} is replaced with page number.
     */
    public function getIndexUrlPattern(): string;

    /**
     * Maximum pages to scrape per run.
     */
    public function getMaxPages(): int;

    /**
     * CSS selectors for extracting data from listing cards on the index page.
     */
    public function getIndexSelectors(): array;

    /**
     * CSS selectors for extracting data from a single listing detail page.
     */
    public function getDetailSelectors(): array;

    /**
     * Ordered sequence of pipeline stage classes to process raw scraped data.
     */
    public function getPipelineStages(): array;

    /**
     * Export formats after scraping. Excel for reports, JSON for APIs, CSV for data import.
     */
    public function getExports(): array;

    /**
     * Auth strategy for this site. Returns null if no login required.
     */
    public function getAuthStrategy(): ?AuthStrategyInterface;

    /**
     * Seconds to wait between requests to avoid rate limiting and IP bans.
     */
    public function getRequestDelay(): int;

    /**
     * Browser settings: headless mode, window size, and user agent string.
     */
    public function getBrowserConfig(): array;
}
