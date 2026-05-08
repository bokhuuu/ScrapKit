<?php

declare(strict_types=1);

namespace App\Scrapers\Profiles;

use App\Scrapers\Base\AbstractScraperProfile;

/**
 * Scraper profile for list.am : Armenia's largest classifieds platform.
 * Targets the Apartments for Sale category (category/60) in Yerevan.
 * Used for Colliers International market entry research.
 */
class ListAmProfile extends AbstractScraperProfile
{
    public function getName(): string
    {
        return 'listam';
    }

    public function getBaseUrl(): string
    {
        return 'https://www.list.am';
    }

    /**
     * list.am is sensitive to rapid requests. 3 seconds prevents IP bans.
     */
    public function getRequestDelay(): int
    {
        return 3;
    }

    /**
     * list.am uses path-based pagination: /category/60/1, /category/60/2
     */
    public function getIndexUrlPattern(): string
    {
        return 'https://www.list.am/category/60/{page}';
    }

    /**
     * 50 pages × ~20 listings = ~1000 apartments per run.
     * Sufficient for district-level price analysis.
     */
    public function getMaxPages(): int
    {
        return 50;
    }

    /**
     * Selectors discovered via DevTools inspection of list.am/category/60.
     * div.gl = card container, div.p = price, div.at = location + specs.
     */
    public function getIndexSelectors(): array
    {
        return [
            'listing_container' => 'div.gl',
            'detail_link'       => 'a[href*="/en/item/"]',
            'price'             => 'div.p',
            'location'          => 'div.at',
            'seller_type'       => 'span.ge5',
        ];
    }

    /**
     * Selectors for the individual listing detail page.
     * span[itemprop="price"][content] gives clean numeric value
     * without parsing the formatted "$750,000" string.
     */
    public function getDetailSelectors(): array
    {
        return [
            'price'         => 'span[itemprop="price"]',
            'price_value'   => 'span[itemprop="price"][content]',
            'address'       => 'p.te10',
            'specs'         => 'div.at2',
            'description'   => 'div.body[itemprop="description"]',
            'seller_name'   => 'div.pwname',
            'images'        => 'div.tico img',
        ];
    }
}
