<?php

namespace App\Observers;

use App\Models\Booking;
use App\Models\Tour;

class BookingObserver
{
    public function created(Booking $booking): void
    {
        Tour::syncBookingCountFor($booking->tour_slug);
    }

    public function updated(Booking $booking): void
    {
        Tour::syncBookingCountFor($booking->tour_slug);

        if ($booking->wasChanged('tour_slug')) {
            Tour::syncBookingCountFor($booking->getOriginal('tour_slug'));
        }
    }

    public function deleted(Booking $booking): void
    {
        Tour::syncBookingCountFor($booking->tour_slug);
    }

    public function restored(Booking $booking): void
    {
        Tour::syncBookingCountFor($booking->tour_slug);
    }

    public function forceDeleted(Booking $booking): void
    {
        Tour::syncBookingCountFor($booking->tour_slug);
    }
}
