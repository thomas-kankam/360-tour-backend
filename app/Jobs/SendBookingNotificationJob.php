<?php

namespace App\Jobs;

use App\Mail\BookingNotificationEmail;
use App\Models\Actor;
use App\Models\Admin;
use App\Models\Booking;
use App\Models\Client;
use App\Services\SmsService;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Mail;

class SendBookingNotificationJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public function __construct(
        public string $bookingCode,
        public string $event,
        public array $recipients = ['client', 'admin'],
    ) {}

    public function handle(SmsService $smsService): void
    {
        $booking = Booking::query()
            ->with('tour')
            ->where('booking_code', $this->bookingCode)
            ->first();

        if (! $booking) {
            return;
        }

        if (in_array('client', $this->recipients, true)) {
            $client = $booking->client_slug
                ? Client::query()->where('client_slug', $booking->client_slug)->first()
                : null;

            $this->notifyActor($smsService, $client, $booking, 'client');
        }

        if (in_array('admin', $this->recipients, true)) {
            $admin = $booking->admin_slug
                ? Admin::query()->where('admin_slug', $booking->admin_slug)->first()
                : null;

            $this->notifyActor($smsService, $admin, $booking, 'admin');
        }
    }

    protected function notifyActor(SmsService $smsService, ?Actor $actor, Booking $booking, string $audience): void
    {
        if (! $actor) {
            return;
        }

        [$headline, $body, $smsMessage, $actionUrl] = $this->buildContent($booking, $audience);
        $recipientName = trim($actor->first_name . ' ' . ($actor->last_name ?? ''));

        if (! empty($actor->email)) {
            Mail::to($actor->email)->send(new BookingNotificationEmail(
                recipientName: $recipientName ?: $actor->email,
                headline: $headline,
                body: $body,
                booking: $booking,
                actionUrl: $actionUrl,
            ));
        }

        if (! empty($actor->phone_number)) {
            $smsService->send($actor->phone_number, $smsMessage);
        }
    }

    protected function buildContent(Booking $booking, string $audience): array
    {
        $tourName = $booking->tour?->name ?? 'your tour';
        $bookingCode = $booking->booking_code;
        $selectedDate = $booking->selected_date?->format('M j, Y') ?? 'the selected date';
        $amount = number_format((float) $booking->amount, 2) . ' ' . $booking->currency;

        $actionUrl = match ($audience) {
            'admin' => config('custom.urls.admin_url'),
            default => config('custom.urls.client_url'),
        };

        return match ($this->event) {
            'booking_created' => match ($audience) {
                'admin' => $booking->booked_by_type === 'admin'
                    ? [
                        'Booking created',
                        "You created booking {$bookingCode} for {$tourName} on {$selectedDate}.",
                        "360 Tours Ghana: you created booking {$bookingCode} for {$tourName} on {$selectedDate}.",
                        $actionUrl,
                    ]
                    : [
                        'New booking received',
                        "A client placed booking {$bookingCode} for {$tourName} on {$selectedDate}.",
                        "New 360 Tours Ghana booking {$bookingCode} for {$tourName} on {$selectedDate}.",
                        $actionUrl,
                    ],
                default => $booking->booked_by_type === 'admin'
                    ? [
                        'Booking created for you',
                        "Booking {$bookingCode} for {$tourName} on {$selectedDate} was created on your behalf.",
                        "360 Tours Ghana: booking {$bookingCode} for {$tourName} on {$selectedDate} was created for you.",
                        $actionUrl,
                    ]
                    : [
                        'Booking submitted',
                        "Your booking {$bookingCode} for {$tourName} on {$selectedDate} was submitted successfully.",
                        "360 Tours Ghana: booking {$bookingCode} for {$tourName} on {$selectedDate} submitted.",
                        $actionUrl,
                    ],
            },
            'booking_updated' => match ($audience) {
                'admin' => [
                    'Booking updated',
                    "Booking {$bookingCode} for {$tourName} was updated.",
                    "360 Tours Ghana: booking {$bookingCode} for {$tourName} was updated.",
                    $actionUrl,
                ],
                default => [
                    'Booking updated',
                    "Your booking {$bookingCode} for {$tourName} was updated successfully.",
                    "360 Tours Ghana: your booking {$bookingCode} for {$tourName} was updated.",
                    $actionUrl,
                ],
            },
            'payment_success' => match ($audience) {
                'admin' => [
                    'Payment successful',
                    "Payment for booking {$bookingCode} ({$tourName}) was successful. Amount paid: {$amount}.",
                    "360 Tours Ghana: payment for booking {$bookingCode} successful. Amount: {$amount}.",
                    $actionUrl,
                ],
                default => [
                    'Payment successful',
                    "Payment for booking {$bookingCode} ({$tourName}) was successful. Amount paid: {$amount}.",
                    "360 Tours Ghana: payment for booking {$bookingCode} successful. Amount: {$amount}.",
                    $actionUrl,
                ],
            },
            'payment_failed' => match ($audience) {
                'admin' => [
                    'Payment failed',
                    "Payment for booking {$bookingCode} ({$tourName}) failed.",
                    "360 Tours Ghana: payment for booking {$bookingCode} failed.",
                    $actionUrl,
                ],
                default => [
                    'Payment failed',
                    "Payment for booking {$bookingCode} ({$tourName}) failed. Please create a new booking or try again.",
                    "360 Tours Ghana: payment for booking {$bookingCode} failed. Please try again.",
                    $actionUrl,
                ],
            },
            default => [
                'Booking notification',
                "There is an update for booking {$bookingCode}.",
                "360 Tours Ghana update for booking {$bookingCode}.",
                $actionUrl,
            ],
        };
    }
}
