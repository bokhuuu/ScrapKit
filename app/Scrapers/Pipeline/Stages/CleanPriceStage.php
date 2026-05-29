<?php

declare(strict_types=1);

namespace App\Scrapers\Pipeline\Stages;

use App\DTOs\ListingDTO;
use App\Scrapers\Pipeline\PipelineStageInterface;

/**
 * Strips currency symbols, spaces and formatting characters
 * from the price field and converts it to a clean integer.
 *
 * Leaves price as null if no numeric value can be extracted.
 */
final class CleanPriceStage implements PipelineStageInterface
{
    public function handle(ListingDTO $dto): ListingDTO
    {
        $dto->price = $this->cleanPrice($dto->price);

        return $dto;
    }

    /**
     * Remove all non-numeric characters and return as float.
     * Returns null if the cleaned value is empty or zero.
     */
    private function cleanPrice(mixed $value): ?float
    {
        if ($value === null) {
            return null;
        }

        $cleaned = preg_replace('/[^0-9]/', '', (string) $value);

        if ($cleaned === '' || $cleaned === '0') {
            return null;
        }

        return (float) $cleaned;
    }
}
