<?php

declare(strict_types=1);

namespace App\Scrapers\Pipeline\Stages;

use App\DTOs\ListingDTO;
use App\Scrapers\Pipeline\PipelineStageInterface;

/**
 * Normalizes building type to lowercase with consistent
 * single spacing for reliable grouping and filtering.
 *
 * Leaves buildingType as null if value is missing.
 */
final class CleanBuildingTypeStage implements PipelineStageInterface
{
    public function handle(ListingDTO $dto): ListingDTO
    {
        $dto->buildingType = $this->cleanBuildingType($dto->buildingType);

        return $dto;
    }

    /**
     * Lowercase the value and collapse multiple spaces into one.
     * Returns null if value is missing.
     */
    private function cleanBuildingType(?string $value): ?string
    {
        if ($value === null) {
            return null;
        }

        $lowercased = strtolower($value);
        $normalized = preg_replace('/\s+/', ' ', $lowercased);

        return trim($normalized);
    }
}
