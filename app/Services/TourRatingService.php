<?php

namespace App\Services;

use App\Models\Rating;
use App\Models\Tour;

class TourRatingService
{
    public function syncForTour(?string $tourSlug): void
    {
        if (! $tourSlug) {
            return;
        }

        $approved = Rating::query()
            ->where('tour_slug', $tourSlug)
            ->where('status', 'approved')
            ->get(['rating']);

        $reviewCount = $approved->count();
        $averageRating = $reviewCount > 0
            ? round($approved->avg('rating'), 1)
            : 0;

        Tour::query()
            ->where('tour_slug', $tourSlug)
            ->update([
                'rating' => $averageRating,
                'review_count' => $reviewCount,
            ]);
    }
}
