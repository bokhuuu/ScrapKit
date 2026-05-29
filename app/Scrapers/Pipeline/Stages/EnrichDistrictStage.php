<?php

declare(strict_types=1);

namespace App\Scrapers\Pipeline\Stages;

use App\DTOs\ListingDTO;
use App\Scrapers\Pipeline\PipelineStageInterface;

/**
 * Extracts the district name from the raw address field
 * and populates the district field on the DTO.
 *
 * Only runs extraction if district is not already set.
 * Leaves district as null if no known district is found.
 */
final class EnrichDistrictStage implements PipelineStageInterface
{
    /**
     * Known districts for the target city.
     * Provided by the site profile at pipeline construction time.
     */
    public function __construct(
        private readonly array $districts,
    ) {}

    public function handle(ListingDTO $dto): ListingDTO
    {
        $dto->district = $this->resolveDistrict($dto->district, $dto->address);

        return $dto;
    }

    /**
     * Return existing district if already set, otherwise
     * attempt to extract it from the address string.
     */
    private function resolveDistrict(?string $district, ?string $address): ?string
    {
        if ($district !== null) {
            return $district;
        }

        if ($address === null) {
            return null;
        }

        foreach ($this->districts as $known) {
            if (stripos($address, $known) !== false) {
                return $known;
            }
        }

        return null;
    }
}
