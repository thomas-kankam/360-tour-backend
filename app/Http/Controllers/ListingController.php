<?php

namespace App\Http\Controllers;

use App\Models\Tour;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class ListingController extends Controller
{
    use \App\Traits\ListingMapper;

    public function index(Request $request): JsonResponse
    {
        $query = Tour::query()->published();
        if ($request->filled('featured')) {
            $query->where('featured', filter_var($request->featured, FILTER_VALIDATE_BOOLEAN));
        }

        if ($request->filled('country')) {
            $query->where('country', 'like', '%' . $request->country . '%');
        }

        $priceSort = strtolower((string) $request->input('price_amount', ''));
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
}
