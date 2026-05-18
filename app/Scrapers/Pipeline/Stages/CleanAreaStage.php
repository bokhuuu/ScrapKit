<?php

declare(strict_types=1);

namespace App\Scrapers\Pipeline\Stages;

use App\DTOs\ListingDTO;
use App\Scrapers\Pipeline\PipelineStageInterface;

/**
 * Strips unit symbols and formatting from the area field
 * and converts it to a clean float.
 *
 * Leaves area as null if no numeric value can be extracted.
 */
final class CleanAreaStage implements PipelineStageInterface
{
    public function handle(ListingDTO $dto): ListingDTO
    {
        $dto->area = $this->cleanArea($dto->area);

        return $dto;
    }

    /**
     * Remove non-numeric characters except decimal separators,
     * normalize comma to period and return as float.
     * Returns null if no valid numeric value can be extracted.
     */
    private function cleanArea(mixed $value): ?float
    {
        if ($value === null) {
            return null;
        }

        $cleaned = preg_replace('/[^0-9,.]/', '', (string) $value);
        $normalized = str_replace(',', '.', $cleaned);

        if ($normalized === '' || (float) $normalized === 0.0) {
            return null;
        }

        return (float) $normalized;
    }
}
