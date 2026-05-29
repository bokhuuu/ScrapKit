<?php

declare(strict_types=1);

namespace App\DTOs;

use Carbon\Carbon;

final class ListingDTO
{
    public function __construct(
        public string $externalId,
        public string $url,
        public string $sourceProfileName,

        public string $listingType,
        public string $propertyType,

        public ?float $price,
        public ?string $currency,
        public ?float $pricePerSqm,

        public ?float $area,
        public ?float $livingArea,
        public ?float $kitchenArea,

        public ?int $rooms,
        public ?int $floor,
        public ?int $totalFloors,
        public ?int $yearBuilt,
        public ?float $ceilingHeight,
        public ?string $buildingType,
        public ?string $condition,
        public ?bool $isNewBuilding,

        public ?bool $hasBalcony,
        public ?bool $hasFurniture,
        public ?bool $hasElevator,
        public ?bool $hasParking,
        public ?bool $hasGarage,

        public ?string $country,
        public ?string $city,
        public ?string $district,
        public ?string $address,
        public ?float $latitude,
        public ?float $longitude,

        public ?string $phone,

        public array $imageUrls,

        public ?string $title,
        public ?string $description,
        public ?bool $isAgency,
        public ?Carbon $listedAt,
        public Carbon $scrapedAt,
    ) {}

    /**
     * Create a ListingDTO from a raw associative array.
     * Used by scrapers after extracting raw page data.
     */
    public static function fromArray(array $data): self
    {
        return new self(
            externalId: $data['external_id'],
            url: $data['url'],
            sourceProfileName: $data['source_profile_name'],

            listingType: $data['listing_type'],
            propertyType: $data['property_type'],

            price: isset($data['price']) ? (float) $data['price'] : null,
            currency: $data['currency'] ?? null,
            pricePerSqm: isset($data['price_per_sqm']) ? (float) $data['price_per_sqm'] : null,

            area: isset($data['area']) ? (float) $data['area'] : null,
            livingArea: isset($data['living_area']) ? (float) $data['living_area'] : null,
            kitchenArea: isset($data['kitchen_area']) ? (float) $data['kitchen_area'] : null,

            rooms: isset($data['rooms']) ? (int) $data['rooms'] : null,
            floor: isset($data['floor']) ? (int) $data['floor'] : null,
            totalFloors: isset($data['total_floors']) ? (int) $data['total_floors'] : null,
            yearBuilt: isset($data['year_built']) ? (int) $data['year_built'] : null,
            ceilingHeight: isset($data['ceiling_height']) ? (float) $data['ceiling_height'] : null,
            buildingType: $data['building_type'] ?? null,
            condition: $data['condition'] ?? null,
            isNewBuilding: isset($data['is_new_building'])
                ? filter_var($data['is_new_building'], FILTER_VALIDATE_BOOLEAN, FILTER_NULL_ON_FAILURE)
                : null,

            hasBalcony: $data['has_balcony'] ?? null,
            hasFurniture: $data['has_furniture'] ?? null,
            hasElevator: $data['has_elevator'] ?? null,
            hasParking: $data['has_parking'] ?? null,
            hasGarage: $data['has_garage'] ?? null,

            country: $data['country'] ?? null,
            city: $data['city'] ?? null,
            district: $data['district'] ?? null,
            address: $data['location'] ?? $data['address'] ?? null,
            latitude: isset($data['latitude']) ? (float) $data['latitude'] : null,
            longitude: isset($data['longitude']) ? (float) $data['longitude'] : null,

            phone: $data['phone'] ?? null,

            imageUrls: $data['image_urls'] ?? [],

            title: $data['title'] ?? null,
            description: $data['description'] ?? null,
            isAgency: $data['is_agency'] ?? null,
            listedAt: isset($data['listed_at']) ? Carbon::parse($data['listed_at']) : null,
            scrapedAt: isset($data['scraped_at']) ? Carbon::parse($data['scraped_at']) : Carbon::now(),
        );
    }

    /**
     * Convert DTO to plain array for the repository layer.
     * Keys use snake_case to match database column names.
     */
    public function toArray(): array
    {
        return [
            'external_id' => $this->externalId,
            'url' => $this->url,
            'source_profile_name' => $this->sourceProfileName,

            'listing_type' => $this->listingType,
            'property_type' => $this->propertyType,

            'price' => $this->price,
            'currency' => $this->currency,
            'price_per_sqm' => $this->pricePerSqm,

            'area' => $this->area,
            'living_area' => $this->livingArea,
            'kitchen_area' => $this->kitchenArea,

            'rooms' => $this->rooms,
            'floor' => $this->floor,
            'total_floors' => $this->totalFloors,
            'year_built' => $this->yearBuilt,
            'ceiling_height' => $this->ceilingHeight,
            'building_type' => $this->buildingType,
            'condition' => $this->condition,
            'is_new_building' => $this->isNewBuilding,

            'has_balcony' => $this->hasBalcony,
            'has_furniture' => $this->hasFurniture,
            'has_elevator' => $this->hasElevator,
            'has_parking' => $this->hasParking,
            'has_garage' => $this->hasGarage,

            'country' => $this->country,
            'city' => $this->city,
            'district' => $this->district,
            'address' => $this->address,
            'latitude' => $this->latitude,
            'longitude' => $this->longitude,

            'phone' => $this->phone,

            'image_urls' => $this->imageUrls,

            'title' => $this->title,
            'description' => $this->description,
            'is_agency' => $this->isAgency,
            'listed_at' => $this->listedAt?->toDateTimeString(),
            'scraped_at' => $this->scrapedAt->toDateTimeString(),
        ];
    }
}
