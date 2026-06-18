<?php

declare(strict_types=1);

namespace App\Scrapers\Pipeline;

use App\DTOs\ListingDTO;
use App\Scrapers\Exceptions\DuplicateListingException;
use App\Scrapers\Exceptions\InvalidListingException;
use Illuminate\Support\Facades\Log;

/**
 * Runs a ListingDTO through an ordered sequence of pipeline stages.
 *
 * Handles InvalidListingException and DuplicateListingException
 * internally. All other exceptions bubble up to the caller.
 */
final class ScraperPipeline
{
    /**
     * Takes the ordered list of pipeline stages to run every listing through.
     */
    public function __construct(
        private readonly array $stages,
    ) {}

    /**
     * Process a DTO through all stages in order.
     * Returns the modified DTO, or null if the listing was skipped.
     */
    public function process(ListingDTO $dto): ?ListingDTO
    {
        try {
            foreach ($this->stages as $stage) {
                $dto = $stage->handle($dto);
            }

            return $dto;
        } catch (DuplicateListingException) {
            return null;
        } catch (InvalidListingException $e) {
            Log::warning('Listing skipped — invalid data', [
                'message' => $e->getMessage(),
                'externalId' => $dto->externalId,
                'source' => $dto->sourceProfileName,
            ]);

            return null;
        }
    }
}
