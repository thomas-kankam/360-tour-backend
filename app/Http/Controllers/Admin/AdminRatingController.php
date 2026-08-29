<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Rating;
use App\Models\UserNotification;
use App\Services\NotificationService;
use App\Services\TourRatingService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class AdminRatingController extends Controller
{
    public function __construct(
        protected TourRatingService $tourRatingService,
        protected NotificationService $notifications,
    ) {}

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

        $clientUrl = NotificationService::clientBaseUrl();
        $tourSlug = $rating->tour_slug;
        $type = $data['status'] === 'approved'
            ? UserNotification::TYPE_RATING_APPROVED
            : UserNotification::TYPE_RATING_REJECTED;

        $this->notifications->notifyClient(
            clientSlug: $rating->client_slug,
            type: $type,
            title: $data['status'] === 'approved' ? 'Your review was approved' : 'Your review was not published',
            body: $data['status'] === 'approved'
                ? 'Your review for ' . ($rating->tour?->name ?? 'a tour') . ' is now live.'
                : 'Your review for ' . ($rating->tour?->name ?? 'a tour') . ' was not published.',
            actionUrl: $clientUrl . '/tours/' . $tourSlug . '#tour-reviews',
            meta: ['rating_uuid' => $rating->rating_uuid, 'tour_slug' => $tourSlug],
        );

        return self::apiResponse(false, 'Action Successful', (string) self::API_SUCCESS, 'Rating updated', $rating->toRatingArray(includeClientEmail: true));
    }
}
