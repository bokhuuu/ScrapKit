<?php

declare(strict_types=1);

namespace App\Scrapers\Profiles;

use App\Scrapers\Base\AbstractScraperProfile;

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
}
