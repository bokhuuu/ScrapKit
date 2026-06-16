<?php

declare(strict_types=1);

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

/**
 * Validates the payload for starting a scrape run.
 *
 * Ensures the source exists in the profile registry
 * and pages is a sensible positive integer before
 * the request ever reaches the controller.
 */
class StartScrapeRequest extends FormRequest
{
    /**
     * All authenticated API users may start a scrape.
     */
    public function authorize(): bool
    {
        return true;
    }

    /**
     * Validation rules for the scrape start payload.
     */
    public function rules(): array
    {
        $validSources = array_keys(config('scraper.profiles', []));

        return [
            'source' => ['required', 'string', 'in:' . implode(',', $validSources)],
            'pages'  => ['sometimes', 'integer', 'min:1', 'max:500'],
        ];
    }
}
