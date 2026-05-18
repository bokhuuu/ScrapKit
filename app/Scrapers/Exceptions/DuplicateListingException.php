<?php

declare(strict_types=1);

namespace App\Scrapers\Exceptions;

use RuntimeException;

/**
 * Thrown when a listing already exists in the database.
 * The pipeline catches this and skips the listing silently
 * without treating it as an error.
 */
final class DuplicateListingException extends RuntimeException {}
