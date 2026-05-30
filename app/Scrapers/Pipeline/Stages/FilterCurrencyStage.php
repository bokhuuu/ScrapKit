<?php

declare(strict_types=1);

namespace App\Scrapers\Pipeline\Stages;

use App\DTOs\ListingDTO;
use App\Scrapers\Exceptions\InvalidListingException;
use App\Scrapers\Pipeline\PipelineStageInterface;

/**
 * Drops listings whose currency is not in the accepted list.
 *
 * Injected per profile - only profiles that require currency filtering
 * include this stage. The default pipeline has no currency opinion.
 */
final class FilterCurrencyStage implements PipelineStageInterface
{
    public function __construct(
        private readonly array $acceptedCurrencies,
    ) {}

    public function handle(ListingDTO $dto): ListingDTO
    {
        if ($dto->currency !== null && ! in_array($dto->currency, $this->acceptedCurrencies, true)) {
            throw new InvalidListingException(
                "Currency '{$dto->currency}' is not accepted. Accepted: " . implode(', ', $this->acceptedCurrencies)
            );
        }

        return $dto;
    }
}
