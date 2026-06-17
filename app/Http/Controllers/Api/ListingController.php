<?php

declare(strict_types=1);

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Listing;
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
    public function __construct(
        private readonly ListingRepository $listingRepository,
    ) {}

    /**
     * Return a paginated list of listings for a given source.
     */
    public function index(Request $request): JsonResponse
    {
        $source = $request->query('source', 'listam');

        $query = Listing::where('source_profile_name', $source);

        if ($request->filled('district')) {
            $query->where('district', $request->query('district'));
        }

        if ($request->filled('min_price')) {
            $query->where('price', '>=', $request->query('min_price'));
        }

        if ($request->filled('max_price')) {
            $query->where('price', '<=', $request->query('max_price'));
        }

        $listings = $query->paginate(50);

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
