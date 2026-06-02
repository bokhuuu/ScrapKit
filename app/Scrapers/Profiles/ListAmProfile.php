<?php

declare(strict_types=1);

namespace App\Scrapers\Profiles;

use App\Scrapers\Base\AbstractScraperProfile;
use App\Scrapers\Notifications\MailNotifier;
use App\Scrapers\Notifications\TelegramNotifier;
use App\Scrapers\Pipeline\Stages\EnrichDistrictStage;
use App\Scrapers\Pipeline\Stages\FilterCurrencyStage;
use App\Scrapers\Sites\ListAmScraper;

/**
 * Scraper profile for list.am : Armenia's largest classifieds platform.
 * Targets the Apartments for Sale category (category/60) in Yerevan.
 * Used for International market entry research.
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
     * list.am uses path-based pagination: /category/60/1, /category/60/2
     */
    public function getIndexUrlPattern(): string
    {
        return 'https://www.list.am/en/category/60/{page}?n=1%2C2%2C3%2C4%2C5%2C6%2C7%2C8%2C9%2C10%2C13%2C11%2C12';
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
            // CSS selectors
            'price'        => 'span[itemprop="price"]',
            'currency'     => 'meta[itemprop="priceCurrency"]',
            'location'     => '#poi-map-anchor',
            'call_button'  => 'a.call',
            'phone_number' => 'span.phone',
            'listing_date' => 'span[itemprop="datePosted"]',
            'images'       => 'img[src*="s.list.am/f/"]',

            // Label strings - used by extractSpecByLabel()
            'area'             => 'Floor Area',
            'floor'            => 'Floor',
            'floor_total'      => 'Floors in the Building',
            'rooms'            => 'Number of Rooms',
            'bathrooms'        => 'Number of Bathrooms',
            'ceiling_height'   => 'Ceiling Height',
            'building_type'    => 'Construction Type',
            'new_construction' => 'New Construction',
            'renovation'       => 'Renovation',
        ];
    }

    /**
     * list.am is sensitive to rapid requests.
     * Configurable via LISTAM_REQUEST_DELAY in .env.
     */
    public function getRequestDelay(): int
    {
        return (int) config('scraper.profile_config.listam.request_delay');
    }

    /**
     * Pages to scrape per run.
     * Configurable via LISTAM_MAX_PAGES in .env.
     */
    public function getMaxPages(): int
    {
        return (int) config('scraper.profile_config.listam.max_pages');
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
            new FilterCurrencyStage(['USD']),
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

    /**
     * Districts that appear as "the center" in h1 titles on list.am.
     * list.am uses colloquial names - we normalize to official district names.
     */
    public function getDistrictAliases(): array
    {
        return [
            'the center' => 'Kentron',
            'center'     => 'Kentron',
        ];
    }

    /**
     * Notify via Telegram and email on scrape events.
     * Both channels active for the Colliers deliverable.
     */
    public function getNotifiers(): array
    {
        return [
            TelegramNotifier::class,
            MailNotifier::class,
        ];
    }
}
