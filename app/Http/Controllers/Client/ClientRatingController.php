<?php

namespace App\Http\Controllers\Client;

use App\Http\Controllers\Controller;
use App\Models\Rating;
use App\Models\Tour;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Str;

class ClientRatingController extends Controller
{
    public function index(Request $request): JsonResponse
    {
        $client = request()->user();

        $query = Rating::query()
            ->with('tour')
            ->where('client_slug', $client->client_slug)
            ->latest();

        $paginator = self::paginateQuery($request, $query);

        return self::paginatedApiResponse('Ratings retrieved', $paginator, fn (Rating $rating) => $rating->toRatingArray());
    }

    public function show(Rating $rating): JsonResponse
    {
        $client = request()->user();

        if ($rating->client_slug !== $client->client_slug) {
            return self::apiResponse(true, 'Action Unsuccessful', (string) self::API_NOT_FOUND, 'Review not found', []);
        }

        $rating->load('tour');

        return self::apiResponse(false, 'Action Successful', (string) self::API_SUCCESS, 'Review retrieved', $rating->toRatingArray());
    }

    public function store(Request $request): JsonResponse
    {
        $client = request()->user();

        $data = $request->validate([
            'tour_slug' => 'required|string|exists:tours,tour_slug',
            'rating' => 'required|integer|min:1|max:5',
            'comment' => 'nullable|string|max:5000',
        ]);

        $tour = Tour::query()->where('tour_slug', $data['tour_slug'])->published()->first();

        if (! $tour) {
            return self::apiResponse(true, 'Action Unsuccessful', (string) self::API_NOT_FOUND, 'Tour not found', []);
        }

        if (Rating::query()->where('tour_slug', $data['tour_slug'])->where('client_slug', $client->client_slug)->exists()) {
            return self::apiResponse(true, 'Action Unsuccessful', (string) self::API_BAD_REQUEST, 'You have already reviewed this tour', []);
        }

        $rating = Rating::create([
            'rating_uuid' => (string) Str::uuid(),
            'tour_slug' => $data['tour_slug'],
            'client_slug' => $client->client_slug,
            'rating' => $data['rating'],
            'comment' => $data['comment'] ?? null,
            'status' => 'pending',
        ]);

        $rating->load('tour');

        return self::apiResponse(false, 'Action Successful', (string) self::API_CREATED, 'Review submitted', $rating->toRatingArray());
    }

    public function update(Request $request, Rating $rating): JsonResponse
    {
        $client = request()->user();

        if ($rating->client_slug !== $client->client_slug) {
            return self::apiResponse(true, 'Action Unsuccessful', (string) self::API_NOT_FOUND, 'Review not found', []);
        }

        if ($rating->isLocked()) {
            return self::apiResponse(true, 'Action Unsuccessful', (string) self::API_FAIL, 'Approved reviews cannot be edited', []);
        }

        $data = $request->validate([
            'rating' => 'sometimes|integer|min:1|max:5',
            'comment' => 'nullable|string|max:5000',
        ]);

        $rating->update([
            'rating' => $data['rating'] ?? $rating->rating,
            'comment' => array_key_exists('comment', $data) ? $data['comment'] : $rating->comment,
            'status' => 'pending',
        ]);

        $rating->load('tour');

        return self::apiResponse(false, 'Action Successful', (string) self::API_SUCCESS, 'Review updated', $rating->toRatingArray());
    }

    public function destroy(Rating $rating): JsonResponse
    {
        $client = request()->user();

        if ($rating->client_slug !== $client->client_slug) {
            return self::apiResponse(true, 'Action Unsuccessful', (string) self::API_NOT_FOUND, 'Review not found', []);
        }

        if ($rating->isLocked()) {
            return self::apiResponse(true, 'Action Unsuccessful', (string) self::API_FAIL, 'Approved reviews cannot be deleted', []);
        }

        $rating->delete();

        return self::apiResponse(false, 'Action Successful', (string) self::API_SUCCESS, 'Review deleted', []);
    }
}
