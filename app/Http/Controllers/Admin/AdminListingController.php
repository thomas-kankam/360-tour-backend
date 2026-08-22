<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Tour;
use App\Support\GhanaRegions;
use App\Traits\ListingMapper;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class AdminListingController extends Controller
{
    use ListingMapper;

    public function index(Request $request): JsonResponse
    {
        $query = Tour::query()->with('admin');

        if ($request->filled('status')) {
            $query->where('status', $request->status);
        }

        $tourType = strtolower((string) $request->input('tour_type', $request->input('tourType', '')));
        if (in_array($tourType, Tour::TYPES, true)) {
            $query->ofType($tourType);
        }

        if ($request->filled('region') && GhanaRegions::exists($request->region)) {
            $query->inRegion((string) $request->region);
        }

        if ($request->filled('search')) {
            $term = '%' . $request->search . '%';
            $query->where(function ($inner) use ($term) {
                $inner->where('name', 'like', $term)
                    ->orWhere('country', 'like', $term)
                    ->orWhere('locations', 'like', $term);
            });
        }

        $paginator = self::paginateQuery($request, $query->latest());

        return self::paginatedApiResponse('Listings retrieved', $paginator, fn(Tour $tour) => $tour->toListingArray());
    }

    public function show(Tour $listing): JsonResponse
    {
        $listing->load('admin');

        return self::apiResponse(false, 'Action Successful', (string) self::API_SUCCESS, 'Listing retrieved', $listing->toListingArray());
    }

    public function store(Request $request): JsonResponse
    {
        $request->validate([
            'name' => 'required|string|max:255',
            'status' => 'nullable|in:draft,published,archived,active,inactive,expired,live',
            'locations' => 'nullable|array',
            'country' => 'nullable|string|max:255',
            'country_code' => 'nullable|string|max:255',
            'countryCode' => 'nullable|string|max:255',
            'categories' => 'nullable|array',
            'tourType' => 'nullable|in:regular,custom',
            'tour_type' => 'nullable|in:regular,custom',
            'duration_days' => 'nullable|integer',
            'durationDays' => 'nullable|integer',
            'duration_label' => 'nullable|string|max:255',
            'durationLabel' => 'nullable|string|max:255',
            'group_size_min' => 'nullable|integer',
            'groupSizeMin' => 'nullable|integer',
            'group_size_max' => 'nullable|integer',
            'groupSizeMax' => 'nullable|integer',
            'group_size_label' => 'nullable|string|max:255',
            'groupSizeLabel' => 'nullable|string|max:255',
            'price_amount' => 'nullable|numeric',
            'priceAmount' => 'nullable|numeric',
            'price_currency' => 'nullable|string|max:255',
            'priceCurrency' => 'nullable|string|max:255',
            'price_label' => 'nullable|string|max:255',
            'priceLabel' => 'nullable|string|max:255',
            'cover_image_url' => 'nullable|string',
            'coverImageUrl' => 'nullable|string',
            'gallery_image_urls' => 'nullable|array',
            'galleryImageUrls' => 'nullable|array',
            'description' => 'nullable|string',
            'highlights' => 'nullable|array',
            'itinerary' => 'nullable|array',
            'included' => 'nullable|array',
            'not_included' => 'nullable|array',
            'notIncluded' => 'nullable|array',
            'departure_dates' => 'nullable|array',
            'departureDates' => 'nullable|array',
            'booking_settings' => 'nullable|array',
            'bookingSettings' => 'nullable|array',
            'slug' => 'nullable|string|max:255|unique:tours,tour_slug',
        ]);

        $adminSlug = request()->user()->admin_slug;
        $attrs = self::mapListingPayloadToAttributes($request->all(), $adminSlug);
        $listing = Tour::create($attrs);

        return self::apiResponse(false, 'Action Successful', (string) self::API_CREATED, 'Listing created', $listing->toListingArray());
    }

    public function update(Request $request, Tour $listing): JsonResponse
    {
        $attrs = self::mapListingPayloadToAttributes(
            $request->all(),
            $listing->admin_slug ?? $listing->created_by_admin_slug ?? request()->user()->admin_slug
        );
        unset($attrs['tour_slug']);
        $listing->update($attrs);

        return self::apiResponse(false, 'Action Successful', (string) self::API_SUCCESS, 'Listing updated', $listing->fresh()->toListingArray());
    }

    public function destroy(Tour $listing): JsonResponse
    {
        $listing->delete();

        return self::apiResponse(false, 'Action Successful', (string) self::API_SUCCESS, 'Listing deleted', []);
    }

    public function updateStatus(Request $request, Tour $listing): JsonResponse
    {
        $request->validate(['status' => 'required|in:draft,published,archived,active,inactive,expired,live']);

        $listing->update(['status' => $request->status]);

        return self::apiResponse(false, 'Action Successful', (string) self::API_SUCCESS, 'Listing status updated', $listing->fresh()->toListingArray());
    }
}
