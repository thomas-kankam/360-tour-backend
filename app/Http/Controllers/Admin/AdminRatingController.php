<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Rating;
use App\Services\TourRatingService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class AdminRatingController extends Controller
{
    public function __construct(protected TourRatingService $tourRatingService) {}

    public function index(Request $request): JsonResponse
    {
        $query = Rating::query()->with(['tour', 'client'])->latest();

        if ($request->filled('status') && in_array($request->status, ['pending', 'approved', 'rejected'], true)) {
            $query->where('status', $request->status);
        }

        if ($request->filled('search')) {
            $term = '%' . trim((string) $request->search) . '%';
            $query->where(function ($builder) use ($term) {
                $builder->where('comment', 'like', $term)
                    ->orWhere('tour_slug', 'like', $term)
                    ->orWhereHas('tour', fn ($tourQuery) => $tourQuery->where('name', 'like', $term))
                    ->orWhereHas('client', function ($clientQuery) use ($term) {
                        $clientQuery->where('first_name', 'like', $term)
                            ->orWhere('last_name', 'like', $term)
                            ->orWhere('email', 'like', $term);
                    });
            });
        }

        $paginator = self::paginateQuery($request, $query);

        return self::paginatedApiResponse('Ratings retrieved', $paginator, fn (Rating $rating) => $rating->toRatingArray(includeClientEmail: true));
    }

    public function update(Request $request, Rating $rating): JsonResponse
    {
        $data = $request->validate([
            'status' => 'required|in:approved,rejected',
        ]);

        $rating->update(['status' => $data['status']]);
        $this->tourRatingService->syncForTour($rating->tour_slug);

        $rating->load(['tour', 'client']);

        return self::apiResponse(false, 'Action Successful', (string) self::API_SUCCESS, 'Rating updated', $rating->toRatingArray(includeClientEmail: true));
    }
}
