<?php

declare(strict_types=1);

namespace App\Scrapers\Sites;

use App\Scrapers\Browser\BaseScraper;
use App\Scrapers\Browser\StealthConfig;
use App\Scrapers\Contracts\ScraperProfileInterface;
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
    public function __construct(ScraperProfileInterface $profile)
    {
        parent::__construct($profile);
    }

    /**
     * Extend base Chrome flags with stealth configuration.
     * This makes our browser indistinguishable from a real human's Chrome.
     */
    protected function chromeArguments(): array
    {
        return [
            ...parent::chromeArguments(),
            ...StealthConfig::chromeFlags(),
        ];
    }

    /**
     * Visit one index page (listing grid) and collect all detail page URLs.
     *
     * Flow:
     *   1. Build the paginated URL from the profile
     *   2. Navigate to it
     *   3. Inject JS stealth patches
     *   4. Find all listing cards on the page
     *   5. Extract the href from each card's link
     *   6. Return the full URLs
     */
    public function crawlIndexPage(int $page): array
    {
        $url = $this->profile->buildIndexUrl($page);

        $this->navigate($url);
        $this->browser->script(StealthConfig::javascriptPatches());

        $urls = [];

        // Each card on the index page matches div.gl
        // Inside each card there is an <a href="/en/item/XXXXXXX">
        // We collect every href and prefix with base URL
        $links = $this->browser->elements(
            $this->profile->getIndexSelectors()['card_link']
        );

        foreach ($links as $link) {
            $href = $link->getAttribute('href');

            if ($href === null || $href === '') {
                continue;
            }

            // Some hrefs are relative (/en/item/123), some absolute
            // Normalize to always full URL
            $urls[] = str_starts_with($href, 'http')
                ? $href
                : $this->profile->getBaseUrl() . $href;
        }

        return $urls;
    }

    /**
     * Visit one detail page (single listing) and extract all available fields.
     *
     * Flow:
     *   1. Navigate to the listing URL
     *   2. Inject JS stealth patches
     *   3. Extract each field using profile selectors
     *   4. Return raw key-value array - pipeline will clean and normalize
     */
    public function crawlDetailPage(string $url): array
    {
        $this->navigate($url);
        $this->browser->script(StealthConfig::javascriptPatches());

        $selectors = $this->profile->getDetailSelectors();

        return [
            'source_url'    => $url,
            'source_id'     => $this->extractSourceId($url),
            'price'         => $this->extractPrice($selectors),
            'currency'      => $this->extractCurrency($selectors),
            'location'      => $this->safeExtract($selectors['location']),
            'district'      => null,
            'area'          => $this->extractSpecByLabel($selectors['area']),
            'rooms'         => $this->extractSpecByLabel($selectors['rooms']),
            'floor'         => $this->extractSpecByLabel($selectors['floor']),
            'total_floors'  => $this->extractSpecByLabel($selectors['floor_total']),
            'building_type' => $this->extractSpecByLabel($selectors['building_type']),
            'description'   => $this->safeExtract($selectors['description']),
            'phone'         => $this->extractPhone($selectors),
            'new_construction' => $this->extractSpecByLabel($selectors['new_construction']),
            'renovation'       => $this->extractSpecByLabel($selectors['renovation']),
            'images'           => $this->extractImageUrls($selectors),
            'listing_date'     => $this->extractListingDate($selectors),
            'scraped_at'       => now()->toDateTimeString(),
        ];
    }

    /**
     * Extract price from the content attribute of the price element.
     *
     * list.am stores the clean numeric price in:
     *   <span itemprop="price" content="150000">$150,000</span>
     *
     * Reading content="" gives us "150000" - no formatting to clean.
     */
    private function extractPrice(array $selectors): ?string
    {
        try {
            return $this->getAttribute($selectors['price'], 'content');
        } catch (Throwable) {
            return null;
        }
    }

    /**
     * Extract currency from the priceCurrency meta element.
     *
     * list.am stores currency in:
     *   <meta itemprop="priceCurrency" content="USD">
     */
    private function extractCurrency(array $selectors): ?string
    {
        try {
            return $this->getAttribute($selectors['currency'], 'content');
        } catch (Throwable) {
            return null;
        }
    }

    /**
     * Extract phone number - requires auth + button click.
     *
     * list.am hides phone numbers behind a "Call" button.
     * Clicking it reveals the number only if the user is logged in.
     * Auth is handled by the auth strategy before this scraper runs.
     *
     * Flow:
     *   1. Check if the call button exists
     *   2. Click it
     *   3. Wait for the phone number to appear in the DOM
     *   4. Read and return it
     */
    private function extractPhone(array $selectors): ?string
    {
        try {
            if (! $this->isPresent($selectors['call_button'])) {
                return null;
            }

            $this->click($selectors['call_button']);
            $this->waitFor($selectors['phone_number']);

            return $this->getText($selectors['phone_number']);
        } catch (Throwable) {
            return null;
        }
    }

    /**
     * Safely extract text from a selector that may not exist.
     *
     * Optional fields (floor, rooms, building type) are not present
     * on every listing. Return null instead of throwing.
     */
    private function safeExtract(string $selector): ?string
    {
        try {
            return $this->isPresent($selector)
                ? $this->getText($selector)
                : null;
        } catch (Throwable) {
            return null;
        }
    }

    /**
     * Pull the listing ID from the detail page URL.
     *
     * list.am URLs follow the pattern: /en/item/23222099
     * We extract the numeric ID at the end for deduplication.
     */
    private function extractSourceId(string $url): ?string
    {
        $parts = explode('/', rtrim($url, '/'));

        return end($parts) ?: null;
    }

    /**
     * Extract a spec value by its label text from the attribute blocks.
     *
     * list.am renders all specs identically - same classes on every field.
     * CSS selectors cannot distinguish floor from rooms from area.
     * We find the right block by matching the label text instead.
     *
     * Handles two layouts:
     * Normal:   first p = value, second p = label  (Floor Area, Floor, Rooms)
     * Reversed: first p = label, second p = value  (Construction Type)
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
     * Extract listing date from the datePosted meta element.
     *
     * list.am stores the clean ISO date in the content attribute:
     *   <span itemprop="datePosted" content="2026-05-25T09:20:09+00:00">
     *
     * We read content="" to get a parseable datetime string.
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
     *
     * list.am serves images from s.list.am/f/ - we select by src pattern
     * since the img elements have no unique class or id.
     * URLs are stored as JSON array - fetched on demand, not downloaded.
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
