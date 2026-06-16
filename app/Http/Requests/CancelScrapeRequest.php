<?php

declare(strict_types=1);

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

/**
 * Validates the payload for cancelling an active scrape run.
 */
class CancelScrapeRequest extends FormRequest
{
    /**
     * All authenticated API users may cancel a scrape.
     */
    public function authorize(): bool
    {
        return true;
    }

    /**
     * Validation rules for the scrape cancel payload.
     */
    public function rules(): array
    {
        return [
            'run_id' => ['required', 'integer', 'exists:scraper_runs,id'],
        ];
    }
}
