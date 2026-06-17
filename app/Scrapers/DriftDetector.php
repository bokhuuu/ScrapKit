<?php

declare(strict_types=1);

namespace App\Scrapers;

use App\Events\ScrapeFailed;
use App\Repositories\ListingRepository;
use Illuminate\Support\Facades\Log;

/**
 * Detects anomalies in scrape results after each completed run.
 *
 * Checks two signals:
 *   1. Listing count too low - suggests broken index page selectors
 *   2. Null rate too high on key fields - suggests broken detail page selectors
 *
 * When drift is detected, fires ScrapeFailed event to reuse existing
 * Telegram and mail notification infrastructure.
 */
class DriftDetector
{
    /**
     * Key fields monitored for null rate drift.
     * These are the fields most likely to break if list.am changes its HTML.
     */
    private const MONITORED_FIELDS = [
        'district',
        'price',
        'area',
    ];

    public function __construct(
        private readonly ListingRepository $listingRepository,
    ) {}

    /**
     * Run all drift checks for a completed scrape run.
     *
     * Fires ScrapeFailed if any check fails.
     * Never throws - drift detection must not crash the completed job.
     */
    public function check(int $scraperRunId, string $source, int $listingCount): void
    {
        $this->checkListingCount($scraperRunId, $source, $listingCount);
        $this->checkNullRates($scraperRunId, $source);
    }

    /**
     * Check if the run returned suspiciously few listings.
     */
    private function checkListingCount(int $scraperRunId, string $source, int $listingCount): void
    {
        $minimum = config('scraper.drift_min_listings');

        if ($listingCount >= $minimum) {
            return;
        }

        $message = "Drift detected: only {$listingCount} listings scraped (minimum: {$minimum})";

        Log::warning('Drift detection: low listing count', [
            'run_id' => $scraperRunId,
            'source' => $source,
            'count' => $listingCount,
            'minimum' => $minimum,
        ]);

        event(new ScrapeFailed(
            scraperRunId: $scraperRunId,
            source: $source,
            errorMessage: $message,
        ));
    }

    /**
     * Check if key fields have a suspiciously high null rate.
     */
    private function checkNullRates(int $scraperRunId, string $source): void
    {
        $maxNullRate = config('scraper.drift_max_null_rate');
        $total = $this->listingRepository->countBySource($source);

        if ($total === 0) {
            return;
        }

        foreach (self::MONITORED_FIELDS as $field) {
            $nullCount = $this->listingRepository->countNullField($source, $field);
            $nullRate = $nullCount / $total;

            if ($nullRate <= $maxNullRate) {
                continue;
            }

            $percentage = round($nullRate * 100);
            $message = "Drift detected: {$percentage}% null rate on '{$field}' (max: ".($maxNullRate * 100).'%)';

            Log::warning('Drift detection: high null rate', [
                'run_id' => $scraperRunId,
                'source' => $source,
                'field' => $field,
                'null_rate' => $nullRate,
                'max' => $maxNullRate,
            ]);

            event(new ScrapeFailed(
                scraperRunId: $scraperRunId,
                source: $source,
                errorMessage: $message,
            ));
        }
    }
}
