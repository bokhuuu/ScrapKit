<?php

declare(strict_types=1);

namespace App\Scrapers\Profiles;

use App\Scrapers\Auth\Contracts\AuthStrategyInterface;
use App\Scrapers\Auth\CookieAuthStrategy;
use App\Scrapers\Auth\FormLoginStrategy;
use App\Scrapers\Base\AbstractScraperProfile;
use App\Scrapers\Notifications\MailNotifier;
use App\Scrapers\Notifications\TelegramNotifier;
use App\Scrapers\Pipeline\Stages\EnrichDistrictStage;
use App\Scrapers\Pipeline\Stages\FilterCurrencyStage;
use App\Scrapers\Sites\ListAmScraper;
use Laravel\Dusk\Browser;

/**
 * Scraper profile for list.am : Armenia's largest classifieds platform.
 * Targets the Apartments for Sale category (category/60) in Yerevan.
 * Used for International market entry research.
 */
class ListAmProfile extends AbstractScraperProfile
{
    /**
     * The short name list.am is registered under everywhere - config, queue jobs,
     * the database and CLI commands all refer to this site by this name.
     */
    public function getName(): string
    {
        return 'listam';
    }

    /**
     * The root web address for list.am, used to build full page URLs and to
     * know which site a browser session belongs to.
     */
    public function getBaseUrl(): string
    {
        return 'https://www.list.am';
    }

    /**
     * The URL pattern for one page of listings, with {page} swapped in by
     * buildIndexUrl(). The long query string isn't part of pagination - it's
     * the filter that restricts results to Yerevan only.
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
            'price' => 'div.p',
            'location' => 'div.at',
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
            'price' => 'span[itemprop="price"]',
            'currency' => 'meta[itemprop="priceCurrency"]',
            'location' => '#poi-map-anchor',
            'call_button' => 'a.call',
            'phone_number' => 'span.phone',
            'listing_date' => 'span[itemprop="datePosted"]',
            'images' => 'img[src*="s.list.am/f/"]',

            // Label strings - used by extractSpecByLabel()
            'area' => 'Floor Area',
            'floor' => 'Floor',
            'floor_total' => 'Floors in the Building',
            'rooms' => 'Number of Rooms',
            'bathrooms' => 'Number of Bathrooms',
            'ceiling_height' => 'Ceiling Height',
            'building_type' => 'Construction Type',
            'new_construction' => 'New Construction',
            'renovation' => 'Renovation',
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
     * Adds two list.am-specific steps to the end of the standard pipeline:
     * one that drops any listing not priced in USD and one that works out
     * which Yerevan district a listing belongs to.
     */
    public function getPipelineStages(): array
    {
        return [
            ...parent::getPipelineStages(),
            app(FilterCurrencyStage::class, ['acceptedCurrencies' => ['USD']]),
            app(EnrichDistrictStage::class, ['districts' => $this->getDistricts()]),
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
            'center' => 'Kentron',
        ];
    }

    /**
     * Notify via Telegram and email on scrape events.
     * Both channels active for real estate market research delivery.
     */
    public function getNotifiers(): array
    {
        return [
            TelegramNotifier::class,
            MailNotifier::class,
        ];
    }

    /**
     * Generic Excel and JSON for reuse across profiles.
     * Real estate market report as the primary client deliverable.
     */
    public function getExports(): array
    {
        return ['excel', 'json', 'real_estate_report'];
    }

    /**
     * Authenticates via cookie restore first, falls back to form login.
     * Credentials read from config/scraper.php → profile_config.listam.auth.
     * Returns null if no browser provided - safe to call from non-browser contexts.
     */
    public function getAuthStrategy(?Browser $browser = null): ?AuthStrategyInterface
    {
        if ($browser === null) {
            return null;
        }

        $formLogin = app(FormLoginStrategy::class, [
            'browser' => $browser,
            'loginUrl' => 'https://www.list.am/en/user/login',
            'emailSelector' => 'input[name="username"]',
            'passwordSelector' => 'input[name="password"]',
            'submitSelector' => 'button[type="submit"]',
            'successSelector' => 'a.logout',
            'email' => config('scraper.profile_config.listam.auth.email'),
            'password' => config('scraper.profile_config.listam.auth.password'),
        ]);

        return app(CookieAuthStrategy::class, [
            'browser' => $browser,
            'profileName' => $this->getName(),
            'baseUrl' => $this->getBaseUrl(),
            'authCheckSelector' => 'a.logout',
            'loginStrategy' => $formLogin,
        ]);
    }

    /**
     * list.am's apartments-for-sale category is sale listings only.
     */
    public function getListingType(): string
    {
        return 'sale';
    }

    /**
     * list.am's category 60 is apartments specifically.
     */
    public function getPropertyType(): string
    {
        return 'apartment';
    }
}
