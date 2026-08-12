<?php

namespace App\Console\Commands;

use App\Models\Tour;
use Illuminate\Console\Command;

class SyncTourBookingCounts extends Command
{
    protected $signature = 'tours:sync-booking-counts';

    protected $description = 'Backfill booking_count on all tours from existing bookings';

    public function handle(): int
    {
        $tours = Tour::query()->withCount('bookings')->get(['id', 'tour_slug', 'booking_count']);
        $updated = 0;

        foreach ($tours as $tour) {
            if ((int) $tour->booking_count === (int) $tour->bookings_count) {
                continue;
            }

            $tour->update(['booking_count' => $tour->bookings_count]);
            $updated++;
        }

        $this->info("Synced {$updated} of {$tours->count()} tours.");

        return self::SUCCESS;
    }
}
