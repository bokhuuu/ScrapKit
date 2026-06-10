<?php

declare(strict_types=1);

namespace App\Scrapers\Pipeline\Stages;

use App\DTOs\ListingDTO;
use App\Repositories\ListingRepository;
use App\Scrapers\Exceptions\DuplicateListingException;
use App\Scrapers\Pipeline\PipelineStageInterface;

/**
 * Checks whether a listing already exists in the database
 * by source and externalId.
 *
 * Throws DuplicateListingException if a match is found,
 * causing the pipeline to skip this listing entirely.
 */
final class DeduplicateStage implements PipelineStageInterface
{
    public function __construct(
        private readonly ListingRepository $repository,
    ) {}

    public function handle(ListingDTO $dto): ListingDTO
    {
        if ($this->repository->existsByExternalId($dto->sourceProfileName, $dto->externalId)) {
            throw new DuplicateListingException(
                "Listing '{$dto->externalId}' from '{$dto->sourceProfileName}' already exists."
            );
        }

        return $dto;
    }
}
