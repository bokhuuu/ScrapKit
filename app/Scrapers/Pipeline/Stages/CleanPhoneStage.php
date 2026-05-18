<?php

declare(strict_types=1);

namespace App\Scrapers\Pipeline\Stages;

use App\DTOs\ListingDTO;
use App\Scrapers\Pipeline\PipelineStageInterface;

/**
 * Strips all non-numeric characters from the phone field,
 * leaving only digits for consistent storage and deduplication.
 *
 * Leaves phone as null if no numeric value can be extracted.
 */
final class CleanPhoneStage implements PipelineStageInterface
{
    public function handle(ListingDTO $dto): ListingDTO
    {
        $dto->phone = $this->cleanPhone($dto->phone);

        return $dto;
    }

    /**
     * Remove all non-numeric characters and return digits only.
     * Returns null if no digits found.
     */
    private function cleanPhone(?string $value): ?string
    {
        if ($value === null) {
            return null;
        }

        $cleaned = preg_replace('/[^0-9]/', '', $value);

        return $cleaned === '' ? null : $cleaned;
    }
}
