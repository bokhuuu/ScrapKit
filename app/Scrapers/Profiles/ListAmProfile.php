<?php

declare(strict_types=1);

namespace App\Scrapers\Profiles;

use App\Scrapers\Base\AbstractScraperProfile;
use App\Scrapers\Pipeline\Stages\EnrichDistrictStage;
use App\Scrapers\Sites\ListAmScraper;

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
        return 'https://www.list.am/en/category/60/{page}';
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
            'card_link' => 'a[href*="/en/item/"]',
            'price'             => 'div.p',
            'location'          => 'div.at',
        ];
    }

    /**
     * Selectors for the individual listing detail page.
     * Two types of selectors are used:
     *   CSS selectors  - passed directly to the browser (price, currency, location)
     *   Label strings  - used by extractSpecByLabel() to find specs by their
     *                    label text (area, floor, rooms, building_type)
     */
    public function getDetailSelectors(): array
    {
        return [
            // CSS selectors - extracted directly
            'price'       => 'span[itemprop="price"]',
            'currency'    => 'meta[itemprop="priceCurrency"]',
            'location'    => '#poi-map-anchor',
            'description' => 'div.body[itemprop="description"]',
            'call_button' => 'a.call',
            'phone_number' => 'span.phone',
            'listing_date'     => 'span[itemprop="datePosted"]',
            'images' => 'img[src*="s.list.am/f/"]',

            // Label strings - extracted via extractSpecByLabel()
            'area'          => 'Floor Area',
            'floor'         => 'Floor',
            'floor_total'   => 'Floors in the Building',
            'rooms'         => 'Number of Rooms',
            'building_type' => 'Construction Type',
            'new_construction' => 'New Construction',
            'renovation'       => 'Renovation',
        ];
    }

    /**
     * Fields required for a listing to be processable.
     * Passed to ValidateRequiredFieldsStage at pipeline construction.
     */
    public function getRequiredFields(): array
    {
        return [
            'sourceProfileName',
            'externalId',
            'url',
            'price',
        ];
    }

    /**
     * Known Yerevan districts for address-based enrichment.
     * Passed to EnrichDistrictStage at pipeline construction.
     */
    public function getDistricts(): array
    {
        return [
            'Kentron',
            'Arabkir',
            'Avan',
            'Davtashen',
            'Erebuni',
            'Malatia-Sebastia',
            'Nor Nork',
            'Nork-Marash',
            'Nubarashen',
            'Shengavit',
            'Ajapnyak',
            'Kanaker-Zeytun',
        ];
    }

    /**
     * Extends the default pipeline with district enrichment.
     * EnrichDistrictStage is real estate specific — not part of the default pipeline.
     */
    public function getPipelineStages(): array
    {
        return [
            ...parent::getPipelineStages(),
            new EnrichDistrictStage($this->getDistricts()),
        ];
    }

    /**
     * The scraper class responsible for crawling list.am pages.
     * Used by queue jobs to instantiate the correct scraper without hardcoding.
     */
    public function getScraperClass(): string
    {
        return ListAmScraper::class;
    }
}
