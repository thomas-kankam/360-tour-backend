<?php

namespace App\Services;

use App\Exceptions\BookingAmountMismatchException;
use App\Models\Booking;
use App\Models\Payment;
use App\Models\Tour;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;

class BookingService
{
    public function __construct(
        protected PaystackService $paystack,
        protected BookingNotificationService $notifications,
    ) {}

    public function create(array $payload, string $bookedByType, string $bookedBySlug, ?string $clientSlug = null): array
    {
        $tour = Tour::query()->where('tour_slug', $payload['tourSlug'] ?? $payload['tour_slug'])->firstOrFail();
        $bookingType = $payload['bookingType'] ?? $payload['booking_type'] ?? 'group';
        $payload = $this->normalizePayloadForBookingType($payload, $bookingType);
        $travelers = (int) ($payload['travelers'] ?? 1);
        $paymentMode = $payload['paymentMode'] ?? $payload['payment_mode'] ?? 'onsite';
        $providedAmount = $payload['amount'] ?? null;
        $currency = $payload['currency'] ?? $payload['payment_currency'] ?? null;

        if ($bookedByType === 'client' && $tour->status !== 'published') {
            throw new \RuntimeException('This tour is not available for booking.');
        }

        $amount = $this->calculateAmount(
            $tour,
            $travelers,
            $providedAmount !== null ? (float) $providedAmount : null,
            $currency,
        );

        if ($providedAmount !== null && round((float) $providedAmount, 2) !== $amount) {
            throw new BookingAmountMismatchException();
        }

        $booking = Booking::create([
            'booking_code' => '360TG_' . Str::upper(Str::random(6)),
            'client_slug' => $clientSlug,
            'booked_by_type' => $bookedByType,
            'booked_by_slug' => $bookedBySlug,
            'tour_slug' => $tour->tour_slug,
            'booking_type' => $bookingType,
            'selected_date' => $payload['selectedDate'] ?? $payload['selected_date'],
            'travelers' => $travelers,
            'payment_mode' => $paymentMode,
            'payment_status' => $paymentMode === 'online' ? 'pending' : 'onsite',
            'amount' => $amount,
            'currency' => strtoupper((string) ($currency ?: $tour->price_currency ?: 'GHS')),
            'lead_traveler' => $payload['leadTraveler'] ?? $payload['lead_traveler'] ?? [],
            'group_details' => $payload['groupDetails'] ?? $payload['group_details'] ?? null,
            'special_requests' => $payload['specialRequests'] ?? $payload['special_requests'] ?? null,
            'dietary_needs' => $payload['dietaryNeeds'] ?? $payload['dietary_needs'] ?? null,
            'additional_travelers' => $payload['additionalTravelers'] ?? $payload['additional_travelers'] ?? [],
            'status' => 'pending',
            'admin_slug' => $bookedByType === 'admin' ? $bookedBySlug : $tour->admin_slug,
            'created_by_admin_slug' => $bookedByType === 'admin' ? $bookedBySlug : null,
        ]);

        $paymentUrl = null;

        if ($paymentMode === 'online') {
            $paymentUrl = $this->initializeOnlinePayment($booking, $tour, $amount);
        }

        $booking->load('tour');

        $this->notifications->notifyBookingCreated($booking);

        return $booking->toBookingArray($paymentUrl);
    }

    public function updateClientBooking(Booking $booking, array $payload): array
    {
        if ($booking->payment_mode === 'online') {
            throw new \RuntimeException('Online bookings cannot be updated. Please create a new booking instead.');
        }

        $booking->loadMissing('tour');
        $tour = $booking->tour ?? Tour::query()->where('tour_slug', $booking->tour_slug)->firstOrFail();

        $bookingType = $payload['bookingType'] ?? $payload['booking_type'] ?? $booking->booking_type;
        $payload = $this->normalizePayloadForBookingType($payload, $bookingType);
        $travelers = (int) ($payload['travelers'] ?? $booking->travelers);
        $amount = $this->calculateAmount($tour, $travelers);
        $paymentMode = $payload['paymentMode'] ?? $payload['payment_mode'] ?? $booking->payment_mode;
        $switchingToOnline = $paymentMode === 'online' && $booking->payment_mode === 'onsite';

        if ($switchingToOnline) {
            $providedAmount = $payload['amount'] ?? null;

            if ($providedAmount === null || round((float) $providedAmount, 2) !== $amount) {
                throw new BookingAmountMismatchException();
            }
        }

        $updates = array_filter([
            'booking_type' => $bookingType !== $booking->booking_type ? $bookingType : null,
            'selected_date' => $payload['selected_date'] ?? $payload['selectedDate'] ?? null,
            'travelers' => isset($payload['travelers']) ? $travelers : null,
            'lead_traveler' => $payload['leadTraveler'] ?? $payload['lead_traveler'] ?? null,
            'special_requests' => $payload['specialRequests'] ?? $payload['special_requests'] ?? null,
            'dietary_needs' => $payload['dietaryNeeds'] ?? $payload['dietary_needs'] ?? null,
        ], fn ($value) => $value !== null);

        if ($bookingType === 'individual') {
            $updates['group_details'] = null;
            $updates['additional_travelers'] = [];
            $updates['travelers'] = 1;
        } else {
            if (array_key_exists('groupDetails', $payload) || array_key_exists('group_details', $payload)) {
                $updates['group_details'] = $payload['groupDetails'] ?? $payload['group_details'];
            }

            if (array_key_exists('additionalTravelers', $payload) || array_key_exists('additional_travelers', $payload)) {
                $updates['additional_travelers'] = $payload['additionalTravelers'] ?? $payload['additional_travelers'] ?? [];
            }
        }

        if (isset($payload['travelers']) || $switchingToOnline) {
            $updates['amount'] = $amount;
        }

        if ($switchingToOnline) {
            $updates['payment_mode'] = 'online';
            $updates['payment_status'] = 'pending';
        }

        $booking->update($updates);
        $booking->load('tour');

        $paymentUrl = null;

        if ($switchingToOnline) {
            $paymentUrl = $this->initializeOnlinePayment($booking, $tour, $amount);
        }

        $this->notifications->notifyBookingUpdated($booking);

        return $booking->toBookingArray($paymentUrl);
    }

    public function updateAdminBooking(Booking $booking, array $payload): array
    {
        if ($booking->payment_mode === 'online' && $this->hasBookingDetailChanges($payload)) {
            throw new \RuntimeException('Online bookings cannot be updated. Please create a new booking instead.');
        }

        $booking->loadMissing('tour');
        $tour = $booking->tour ?? Tour::query()->where('tour_slug', $booking->tour_slug)->firstOrFail();

        $bookingType = $payload['bookingType'] ?? $payload['booking_type'] ?? $booking->booking_type;
        $payload = $this->normalizePayloadForBookingType($payload, $bookingType);
        $travelers = (int) ($payload['travelers'] ?? $booking->travelers);
        $amount = $this->calculateAmount($tour, $travelers);
        $paymentMode = $payload['paymentMode'] ?? $payload['payment_mode'] ?? $booking->payment_mode;
        $switchingToOnline = $paymentMode === 'online' && $booking->payment_mode === 'onsite';

        if ($switchingToOnline) {
            $providedAmount = $payload['amount'] ?? null;

            if ($providedAmount === null || round((float) $providedAmount, 2) !== $amount) {
                throw new BookingAmountMismatchException();
            }
        }

        $updates = array_filter([
            'booking_type' => $bookingType !== $booking->booking_type ? $bookingType : null,
            'selected_date' => $payload['selected_date'] ?? $payload['selectedDate'] ?? null,
            'travelers' => isset($payload['travelers']) ? $travelers : null,
            'lead_traveler' => $payload['leadTraveler'] ?? $payload['lead_traveler'] ?? null,
            'special_requests' => $payload['specialRequests'] ?? $payload['special_requests'] ?? null,
            'dietary_needs' => $payload['dietaryNeeds'] ?? $payload['dietary_needs'] ?? null,
            'status' => $payload['status'] ?? null,
            'payment_status' => $payload['payment_status'] ?? $payload['paymentStatus'] ?? null,
        ], fn ($value) => $value !== null);

        if ($bookingType === 'individual') {
            $updates['group_details'] = null;
            $updates['additional_travelers'] = [];
            $updates['travelers'] = 1;
        } else {
            if (array_key_exists('groupDetails', $payload) || array_key_exists('group_details', $payload)) {
                $updates['group_details'] = $payload['groupDetails'] ?? $payload['group_details'];
            }

            if (array_key_exists('additionalTravelers', $payload) || array_key_exists('additional_travelers', $payload)) {
                $updates['additional_travelers'] = $payload['additionalTravelers'] ?? $payload['additional_travelers'] ?? [];
            }
        }

        if (isset($payload['travelers']) || $switchingToOnline) {
            $updates['amount'] = $amount;
        }

        if ($switchingToOnline) {
            $updates['payment_mode'] = 'online';
            $updates['payment_status'] = 'pending';
        }

        $booking->update($updates);
        $booking->load('tour');

        $paymentUrl = null;

        if ($switchingToOnline) {
            $paymentUrl = $this->initializeOnlinePayment($booking, $tour, $amount);
        }

        $this->notifications->notifyBookingUpdated($booking);

        return $booking->toBookingArray($paymentUrl);
    }

    protected function hasBookingDetailChanges(array $payload): bool
    {
        $detailKeys = [
            'bookingType', 'booking_type', 'selectedDate', 'selected_date', 'travelers',
            'leadTraveler', 'lead_traveler', 'groupDetails', 'group_details',
            'specialRequests', 'special_requests', 'dietaryNeeds', 'dietary_needs',
            'additionalTravelers', 'additional_travelers', 'paymentMode', 'payment_mode', 'amount',
        ];

        foreach ($detailKeys as $key) {
            if (array_key_exists($key, $payload)) {
                return true;
            }
        }

        return false;
    }

    protected function initializeOnlinePayment(Booking $booking, Tour $tour, float $amount): string
    {
        $initialized = $this->initializePaystackTransaction($booking, $tour, $amount);

        Payment::create([
            'payment_slug' => (string) Str::uuid(),
            'booking_code' => $booking->booking_code,
            'paystack_reference' => $initialized['reference'],
            'paystack_access_code' => $initialized['access_code'],
            'amount' => $amount,
            'currency' => $tour->price_currency,
            'status' => 'pending',
            'payment_url' => $initialized['authorization_url'],
            'paystack_response' => $initialized['raw'],
        ]);

        return $initialized['authorization_url'];
    }

    protected function reinitializeOnlinePayment(Payment $payment, Booking $booking, Tour $tour, float $amount): string
    {
        $initialized = $this->initializePaystackTransaction($booking, $tour, $amount);

        $payment->update([
            'paystack_reference' => $initialized['reference'],
            'paystack_access_code' => $initialized['access_code'],
            'amount' => $amount,
            'currency' => $tour->price_currency,
            'status' => 'pending',
            'payment_url' => $initialized['authorization_url'],
            'paystack_response' => $initialized['raw'],
            'paid_at' => null,
        ]);

        return $initialized['authorization_url'];
    }

    protected function initializePaystackTransaction(Booking $booking, Tour $tour, float $amount): array
    {
        $email = $booking->lead_traveler['email'] ?? 'customer@360toursghana.com';
        $initialized = $this->paystack->initializeTransaction(
            email: $email,
            amount: $amount,
            currency: $tour->price_currency,
            metadata: [
                'booking_code' => $booking->booking_code,
                'tour_slug' => $tour->tour_slug,
            ]
        );

        Log::info('Paystack initialized', ['initialized' => $initialized]);

        return $initialized;
    }

    public function markPaidByReference(string $reference, array $paystackData): void
    {
        $payment = Payment::query()->where('paystack_reference', $reference)->with('booking.tour')->first();

        if (! $payment || $payment->status === 'success') {
            return;
        }

        Log::info('Payment found', ['payment' => $payment]);

        $payment->update([
            'status' => 'success',
            'paystack_response' => $paystackData,
            'paid_at' => now(),
        ]);

        $payment->booking?->update([
            'payment_status' => 'paid',
            'status' => 'confirmed',
        ]);

        if ($payment->booking) {
            $this->notifications->notifyPaymentSuccess($payment->booking);
        }

        Log::info('Payment updated', ['payment' => $payment]);
    }

    public function markFailedByReference(string $reference, array $paystackData): void
    {
        $payment = Payment::query()->where('paystack_reference', $reference)->with('booking.tour')->first();

        if (! $payment || $payment->status === 'success') {
            return;
        }

        if ($payment->status === 'failed') {
            return;
        }

        $payment->update([
            'status' => 'failed',
            'paystack_response' => $paystackData,
        ]);

        $payment->booking?->update([
            'payment_status' => 'failed',
        ]);

        if ($payment->booking) {
            $this->notifications->notifyPaymentFailed($payment->booking);
        }
    }

    public function retryClientPayment(Payment $payment): array
    {
        $booking = $payment->booking()->with('tour')->firstOrFail();

        if ($booking->payment_mode !== 'online') {
            throw new \RuntimeException('Only online payments can be retried.');
        }

        if ($payment->status === 'success' || $booking->payment_status === 'paid') {
            throw new \RuntimeException('This payment has already been completed.');
        }

        if (! in_array($payment->status, ['pending', 'failed'], true)) {
            throw new \RuntimeException('This payment cannot be retried.');
        }

        $amount = $this->calculateAmount($booking->tour, (int) $booking->travelers);

        $this->reinitializeOnlinePayment($payment, $booking, $booking->tour, $amount);

        $booking->update([
            'payment_status' => 'pending',
            'amount' => $amount,
        ]);

        return $payment->fresh(['booking.tour'])->toPaymentArray();
    }

    public function recordOnsitePayment(Booking $booking, ?float $providedAmount = null): array
    {
        if ($booking->payment_mode !== 'onsite') {
            throw new \RuntimeException('Only onsite bookings can receive offline payments.');
        }

        if ($booking->payment_status === 'paid') {
            throw new \RuntimeException('This booking has already been marked as paid.');
        }

        if ($booking->payments()->where('status', 'success')->exists()) {
            throw new \RuntimeException('A payment record already exists for this booking.');
        }

        $amount = (float) $booking->amount;

        if ($providedAmount !== null && round((float) $providedAmount, 2) !== round($amount, 2)) {
            throw new BookingAmountMismatchException();
        }

        $payment = Payment::create([
            'payment_slug' => (string) Str::uuid(),
            'booking_code' => $booking->booking_code,
            'paystack_reference' => null,
            'paystack_access_code' => null,
            'amount' => $amount,
            'currency' => $booking->currency,
            'status' => 'success',
            'payment_url' => null,
            'paystack_response' => null,
            'paid_at' => now(),
        ]);

        $booking->update([
            'payment_status' => 'paid',
            'status' => 'confirmed',
        ]);

        $booking->load('tour');
        $this->notifications->notifyPaymentSuccess($booking);

        return $payment->load(['booking.tour'])->toPaymentArray();
    }

    public function calculateAmountForTour(Tour $tour, int $travelers): float
    {
        return $this->calculateAmount($tour, $travelers);
    }

    protected function normalizePayloadForBookingType(array $payload, string $bookingType): array
    {
        if ($bookingType !== 'individual') {
            return $payload;
        }

        $payload['travelers'] = 1;
        $payload['groupDetails'] = null;
        $payload['group_details'] = null;
        $payload['additionalTravelers'] = [];
        $payload['additional_travelers'] = [];

        return $payload;
    }

    protected function calculateAmount(Tour $tour, int $travelers, ?float $providedAmount = null, ?string $currency = null): float
    {
        $currency = strtoupper(trim((string) ($currency ?: $tour->price_currency ?: 'GHS')));
        $unitPrice = $this->resolveTourUnitPrice($tour, $currency);
        $base = round($unitPrice * $travelers, 2);
        $settings = $tour->booking_settings ?? [];
        $depositPercent = max(1, min(100, (int) ($settings['depositPercent'] ?? 100)));
        $depositAmount = round($base * ($depositPercent / 100), 2);

        if ($providedAmount !== null) {
            $provided = round((float) $providedAmount, 2);
            if ($provided === $base || $provided === $depositAmount) {
                return $provided;
            }
        }

        return $base;
    }

    protected function resolveTourUnitPrice(Tour $tour, ?string $currency = null): float
    {
        $currency = strtoupper(trim((string) ($currency ?: $tour->price_currency ?: 'GHS')));

        if ($currency === 'USD') {
            $usd = (float) ($tour->price_amount_usd ?? 0);

            return $usd > 0 ? $usd : (float) $tour->price_amount;
        }

        $ghs = (float) ($tour->price_amount_ghs ?? 0);

        return $ghs > 0 ? $ghs : (float) $tour->price_amount;
    }
}
