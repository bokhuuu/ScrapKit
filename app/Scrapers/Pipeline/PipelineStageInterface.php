<?php

declare(strict_types=1);

namespace App\Scrapers\Pipeline;

use App\DTOs\ListingDTO;

/**
 * Contract for all data processing pipeline stages.
 *
 * Each stage receives a ListingDTO, performs one specific
 * transformation or validation and returns the modified DTO.
 * Stages may throw InvalidListingException if the data is
 * unrecoverable and the listing should be skipped entirely.
 */
interface PipelineStageInterface
{
    public function handle(ListingDTO $dto): ListingDTO;
}
