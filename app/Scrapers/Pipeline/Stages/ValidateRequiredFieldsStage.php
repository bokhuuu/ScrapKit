<?php

declare(strict_types=1);

namespace App\Scrapers\Pipeline\Stages;

use App\DTOs\ListingDTO;
use App\Scrapers\Pipeline\PipelineStageInterface;
use App\Scrapers\Exceptions\InvalidListingException;

/**
 * Validates that all required fields are present on the DTO.
 *
 * Throws InvalidListingException if any required field is
 * null or empty. The pipeline catches this and skips the listing.
 */
final class ValidateRequiredFieldsStage implements PipelineStageInterface
{
    /**
     * Fields that must be present for a listing to be processable.
     * Provided by the site profile at pipeline construction time.
     */
    public function __construct(
        private readonly array $requiredFields,
    ) {}

    public function handle(ListingDTO $dto): ListingDTO
    {
        foreach ($this->requiredFields as $field) {
            if ($this->isMissing($dto->$field)) {
                throw new InvalidListingException(
                    "Required field '{$field}' is missing or empty."
                );
            }
        }

        return $dto;
    }

    private function isMissing(mixed $value): bool
    {
        return $value === null || $value === '';
    }
}
