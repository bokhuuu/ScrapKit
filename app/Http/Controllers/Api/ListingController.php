<?php

declare(strict_types=1);

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Repositories\ListingRepository;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

/**
 * Handles HTTP access to scraped listing data.
 *
 * Delegates all data retrieval to ListingRepository.
 * This controller only receives, delegates and responds.
 */
class ListingController extends Controller
{
    /**
     * Takes the repository used for all listing data access.
     */
    public function __construct(
        private readonly ListingRepository $listingRepository,
    ) {}

    /**
     * Return a paginated list of listings for a given source.
     */
    public function index(Request $request): JsonResponse
    {
        $source = $request->query('source', 'listam');

        $listings = $this->listingRepository->paginateBySource($source, [
            'district' => $request->query('district'),
            'min_price' => $request->query('min_price'),
            'max_price' => $request->query('max_price'),
        ]);

        return response()->json($listings);
    }

    /**
     * Return aggregated price statistics grouped by district.
     *
     * Results are cached for 1 hour in Redis — see ListingRepository.
     */
    public function stats(Request $request): JsonResponse
    {
        $source = $request->query('source', 'listam');

        $stats = $this->listingRepository->getDistrictPriceStats($source);

        return response()->json([
            'source' => $source,
            'data' => $stats,
        ]);
    }
}
