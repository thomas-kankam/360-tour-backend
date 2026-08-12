<?php

namespace App\Services;

use App\Jobs\SendBookingNotificationJob;
use App\Models\Booking;

class BookingNotificationService
{
    public function notifyBookingCreated(Booking $booking): void
    {
        SendBookingNotificationJob::dispatch($booking->booking_code, 'booking_created');
    }

    public function notifyBookingUpdated(Booking $booking): void
    {
        SendBookingNotificationJob::dispatch($booking->booking_code, 'booking_updated');
    }

    public function notifyPaymentSuccess(Booking $booking): void
    {
        SendBookingNotificationJob::dispatch($booking->booking_code, 'payment_success');
    }

    public function notifyPaymentFailed(Booking $booking): void
    {
        SendBookingNotificationJob::dispatch($booking->booking_code, 'payment_failed');
    }
}
