<?php

namespace App\Http\Controllers;

use App\Models\Rating;
use App\Models\Tour;
use App\Support\GhanaRegions;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class ListingController extends Controller
{
    use \App\Traits\ListingMapper;

    public function index(Request $request): JsonResponse
    {
        $query = Tour::query()->published();

        if ($request->filled('country')) {
            $query->where('country', 'like', '%' . $request->country . '%');
        }

        if ($request->filled('region') && GhanaRegions::exists($request->region)) {
            $query->inRegion((string) $request->region);
        }

        $tourType = strtolower((string) $request->input('tour_type', $request->input('tourType', '')));
        if (in_array($tourType, Tour::TYPES, true)) {
            $query->ofType($tourType);
        }

        $priceSort = strtolower((string) $request->input('price_amount', $request->input('sort_by_price', '')));
        $dateSort = strtolower((string) $request->input('sort_by', ''));

        if (in_array($priceSort, ['asc', 'desc'], true)) {
            $query->orderBy('price_amount', $priceSort);
        } elseif (in_array($dateSort, ['asc', 'desc'], true)) {
            $query->orderBy('created_at', $dateSort);
        } else {
            $query->latest();
        }

        $paginator = self::paginateQuery($request, $query);

        return self::paginatedApiResponse('Listings retrieved', $paginator, fn(Tour $tour) => $tour->toListingArray());
    }

    /**
     * Region facets for the tours browser: every Ghana region that currently has
     * at least one published tour, with its live count.
     */
    public function regions(): JsonResponse
    {
        $tours = Tour::query()
            ->published()
            ->select(['locations', 'regions'])
            ->get();

        $counts = [];
        foreach ($tours as $tour) {
            $regions = $tour->regions ?: GhanaRegions::resolveFromLocations($tour->locations ?? []);
            foreach ($regions as $regionId) {
                $counts[$regionId] = ($counts[$regionId] ?? 0) + 1;
            }
        }

        $facets = [];
        foreach (GhanaRegions::REGIONS as $id => $label) {
            if (($counts[$id] ?? 0) > 0) {
                $facets[] = ['id' => $id, 'label' => $label, 'count' => $counts[$id]];
            }
        }

        return self::apiResponse(false, 'Action Successful', (string) self::API_SUCCESS, 'Regions retrieved', [
            'total' => $tours->count(),
            'regions' => $facets,
        ]);
    }

    public function random(): JsonResponse
    {
        $listings = Tour::query()
            ->published()
            ->inRandomOrder()
            ->limit(8)
            ->get()
            ->map(fn (Tour $tour) => $tour->toListingArray())
            ->values()
            ->all();

        return self::apiResponse(false, 'Action Successful', (string) self::API_SUCCESS, 'Random listings retrieved', $listings);
    }

    public function show(Tour $listing): JsonResponse
    {
        if ($listing->status !== 'published') {
            return self::apiResponse(true, 'Action Unsuccessful', (string) self::API_NOT_FOUND, 'Listing not found', []);
        }

        return self::apiResponse(false, 'Action Successful', (string) self::API_SUCCESS, 'Listing retrieved', $listing->toListingArray());
    }

    public function reviews(Request $request, Tour $listing): JsonResponse
    {
        if ($listing->status !== 'published') {
            return self::apiResponse(true, 'Action Unsuccessful', (string) self::API_NOT_FOUND, 'Listing not found', []);
        }

        $query = $listing->ratings()
            ->with(['client', 'tour'])
            ->where('status', 'approved')
            ->latest();

        $paginator = self::paginateQuery($request, $query);

        return self::paginatedApiResponse('Reviews retrieved', $paginator, fn (Rating $rating) => $rating->toPublicReviewArray());
    }
}
