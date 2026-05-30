<?php

declare(strict_types=1);

namespace App\Scrapers\Pipeline\Stages;

use App\DTOs\ListingDTO;
use App\Scrapers\Pipeline\PipelineStageInterface;

/**
 * Trims whitespace and converts empty strings to null
 * across all string fields in the DTO.
 *
 * Must run first in the pipeline so all subsequent stages
 * can trust that string fields are either a real value or null.
 */
final class NormalizeStringFieldsStage implements PipelineStageInterface
{
    public function handle(ListingDTO $dto): ListingDTO
    {
        $dto->sourceProfileName = trim($dto->sourceProfileName);
        $dto->externalId = trim($dto->externalId);
        $dto->url = trim($dto->url);
        $dto->listingType = trim($dto->listingType);
        $dto->propertyType = trim($dto->propertyType);

        $dto->currency = $this->normalizeString($dto->currency);
        $dto->buildingType = $this->normalizeString($dto->buildingType);
        $dto->condition = $this->normalizeString($dto->condition);
        $dto->district = $this->normalizeString($dto->district);
        $dto->address = $this->normalizeString($dto->address);
        $dto->phone = $this->normalizeString($dto->phone);
        $dto->agencyName = $this->normalizeString($dto->agencyName);

        return $dto;
    }

    /**
     * Trim whitespace and convert empty strings to null.
     * Null input returns null - safe to call on optional fields.
     */
    private function normalizeString(?string $value): ?string
    {
        if ($value === null) {
            return null;
        }

        $trimmed = trim($value);

        return $trimmed === '' ? null : $trimmed;
    }
}
