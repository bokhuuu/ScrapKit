<?php

declare(strict_types=1);

namespace App\Scrapers\Sites;

use App\Scrapers\Browser\BaseScraper;
use App\Scrapers\Browser\ProxyResolver;
use App\Scrapers\Browser\StealthConfig;
use App\Scrapers\Contracts\ScraperProfileInterface;
use Laravel\Dusk\Browser;
use Throwable;

/**
 * Scraper implementation for list.am - Armenia's largest classifieds platform.
 *
 * Extends BaseScraper and implements the two required crawl methods:
 *   - crawlIndexPage()  → collects detail page URLs from a listing grid
 *   - crawlDetailPage() → extracts all fields from a single listing
 *
 * All browser interaction is inherited from BaseScraper.
 * All selectors and URLs come from ListAmProfile - nothing hardcoded here.
 */
class ListAmScraper extends BaseScraper
{
    public function __construct(
        ScraperProfileInterface $profile,
        ProxyResolver $proxyResolver,
        ?Browser $browser = null,
    ) {
        parent::__construct($profile, $proxyResolver, $browser);
    }

    protected function chromeArguments(): array
    {
        return [
            ...parent::chromeArguments(),
            ...StealthConfig::chromeFlags(),
        ];
    }

    /**
     * Visit one index page and collect all detail page URLs.
     */
    public function crawlIndexPage(int $page): array
    {
        $url = $this->profile->buildIndexUrl($page);

        $this->navigate($url);
        $this->browser->script(StealthConfig::javascriptPatches());

        $urls = [];

        $links = $this->browser->elements(
            $this->profile->getIndexSelectors()['card_link']
        );

        foreach ($links as $link) {
            $href = $link->getAttribute('href');

            if ($href === null || $href === '') {
                continue;
            }

            $urls[] = str_starts_with($href, 'http')
                ? $href
                : $this->profile->getBaseUrl() . $href;
        }

        return $urls;
    }

    /**
     * Visit one detail page and extract all available fields.
     * Returns raw key-value array - pipeline cleans and normalizes.
     */
    public function crawlDetailPage(string $url): array
    {
        $this->navigate($url);
        $this->browser->script(StealthConfig::javascriptPatches());

        $selectors = $this->profile->getDetailSelectors();

        return [
            'external_id' => $this->extractSourceId($url),
            'url' => strtok($url, '?'),
            'source_profile_name' => $this->profile->getName(),
            'listing_type' => 'sale',
            'property_type' => 'apartment',
            'price' => $this->extractPrice($selectors),
            'currency' => $this->extractCurrency($selectors),
            'price_per_sqm' => null,
            'location' => $this->safeExtract($selectors['location']),
            'district' => $this->extractDistrict(),
            'area' => $this->extractSpecByLabel($selectors['area']),
            'rooms' => $this->extractSpecByLabel($selectors['rooms']),
            'bathrooms' => $this->extractSpecByLabel($selectors['bathrooms']),
            'floor' => $this->extractSpecByLabel($selectors['floor']),
            'total_floors' => $this->extractSpecByLabel($selectors['floor_total']),
            'ceiling_height' => $this->extractSpecByLabel($selectors['ceiling_height']),
            'building_type' => $this->extractSpecByLabel($selectors['building_type']),
            'is_new_building' => $this->extractSpecByLabel($selectors['new_construction']),
            'condition' => $this->extractSpecByLabel($selectors['renovation']),
            'agency_name' => $this->extractAgencyName(),
            'phone' => $this->extractPhone($selectors),
            'image_urls' => $this->extractImageUrls($selectors),
            'listing_date' => $this->extractListingDate($selectors),
            'scraped_at' => $this->scrapedAt(),
        ];
    }

    /**
     * Extract district by matching document.title against known Yerevan districts.
     * Falls back to aliases (e.g. "the center" → "Kentron").
     * Returns null if no district found - listing may be outside Yerevan.
     */
    private function extractDistrict(): ?string
    {
        try {
            $result = $this->browser->script('return document.title;');
            $title = $result[0] ?? '';

            if ($title === '') {
                return null;
            }

            foreach ($this->profile->getDistricts() as $district) {
                if (stripos($title, $district) !== false) {
                    return $district;
                }
            }

            foreach ($this->profile->getDistrictAliases() as $alias => $district) {
                if (stripos($title, $alias) !== false) {
                    return $district;
                }
            }

            return null;
        } catch (Throwable) {
            return null;
        }
    }

    /**
     * Extract agency name from the agency badge on the detail page.
     *
     * list.am shows a badge next to the listing code.
     * The badge uses obfuscated class span.ge5 - we match by text content instead.
     * Owner listings show no Agency badge - returns null in that case.
     */
    private function extractAgencyName(): ?string
    {
        try {
            $result = $this->browser->script("
            const spans = document.querySelectorAll('span.ge5');
            for (const span of spans) {
                if (span.textContent.trim() === 'Agency') return 'Agency';
            }
            return null;
        ");

            return $result[0] ?? null;
        } catch (Throwable) {
            return null;
        }
    }

    /**
     * Extract price from the content attribute of the price element.
     * list.am stores the clean numeric price in content="" to avoid parsing formatted text.
     */
    private function extractPrice(array $selectors): ?string
    {
        try {
            if (! $this->isPresent($selectors['price'])) {
                return null;
            }

            return $this->getAttribute($selectors['price'], 'content');
        } catch (Throwable) {
            return null;
        }
    }

    /**
     * Extract currency from the priceCurrency meta element content attribute.
     */
    private function extractCurrency(array $selectors): ?string
    {
        try {
            return $this->getAttribute($selectors['currency'], 'content');
        } catch (Throwable) {
            return null;
        }
    }

    private function extractPhone(array $selectors): ?string
    {
        try {
            if (! $this->isPresent($selectors['call_button'])) {
                return null;
            }

            $this->ensureAuthenticated();
            $this->click($selectors['call_button']);
            $this->waitFor($selectors['phone_number']);

            return $this->getText($selectors['phone_number']);
        } catch (Throwable) {
            return null;
        }
    }

    /**
     * Extract a spec value by matching its label text in the attribute blocks.
     * list.am renders all specs with identical CSS classes - label text is the only differentiator.
     * Handles both layout orders: value-first and label-first.
     */
    private function extractSpecByLabel(string $label): ?string
    {
        $result = $this->browser->script("
            const blocks = document.querySelectorAll('.at2 .attr-info-wraper');
            for (const block of blocks) {
                const paragraphs = block.querySelectorAll('p');
                if (paragraphs.length < 2) continue;

                const first  = paragraphs[0].textContent.trim();
                const second = paragraphs[1].textContent.trim();

                if (second === '{$label}') return first;
                if (first  === '{$label}') return second;
            }
            return null;
        ");

        return $result[0] ?? null;
    }

    /**
     * Extract listing date from the datePosted meta element content attribute.
     * Returns ISO datetime string or null if not present.
     */
    private function extractListingDate(array $selectors): ?string
    {
        try {
            return $this->getAttribute($selectors['listing_date'], 'content');
        } catch (Throwable) {
            return null;
        }
    }

    /**
     * Collect all listing image URLs from the photo gallery.
     * Normalizes protocol-relative URLs (//) to full https:// URLs.
     * Returns empty array if no images found.
     */
    private function extractImageUrls(array $selectors): array
    {
        try {
            $elements = $this->browser->elements($selectors['images']);
            $urls = [];

            foreach ($elements as $element) {
                $src = $element->getAttribute('src');

                if ($src === null || $src === '') {
                    continue;
                }

                $urls[] = str_starts_with($src, '//')
                    ? 'https:' . $src
                    : $src;
            }

            return $urls;
        } catch (Throwable) {
            return [];
        }
    }
}
