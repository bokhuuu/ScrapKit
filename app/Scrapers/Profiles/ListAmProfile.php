<?php

declare(strict_types=1);

namespace App\Scrapers\Profiles;

use App\Scrapers\Auth\Contracts\AuthStrategyInterface;
use App\Scrapers\Contracts\ScraperProfileInterface;

class ListAmProfile implements ScraperProfileInterface
{
    public function getName(): string
    {
        return 'listam';
    }

    public function getBaseUrl(): string
    {
        return 'https://www.list.am';
    }

    public function getIndexUrlPattern(): string
    {
        return 'https://www.list.am/category/60/{page}';
    }

    public function getMaxPages(): int
    {
        return 50;
    }

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

    public function getExports(): array
    {
        return ['excel', 'json'];
    }

    public function getAuthStrategy(): ?AuthStrategyInterface
    {
        return null;
    }

    public function getRequestDelay(): int
    {
        return 3;
    }

    public function getBrowserConfig(): array
    {
        return [
            'headless'    => true,
            'window_size' => '1920,1080',
            'user_agent'  => 'Mozilla/5.0 (X11; Linux x86_64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/122.0.0.0 Safari/537.36',
        ];
    }
}
