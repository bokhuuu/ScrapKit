<?php

declare(strict_types=1);

namespace App\Scrapers\Exceptions;

use RuntimeException;

/**
 * Thrown when a listing is missing required data and cannot
 * be processed. The pipeline catches this exception and skips
 * the listing without interrupting the rest of the scrape run.
 */
final class InvalidListingException extends RuntimeException {}
