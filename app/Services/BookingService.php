<?php

namespace App\Services;

use App\Exceptions\BookingAmountMismatchException;
use App\Models\Booking;
use App\Models\InvoiceRequest;
use App\Models\Payment;
use App\Models\Tour;
use Illuminate\Support\Facades\DB;
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

        $currency = $this->paystack->normalizeCurrency($currency ?: $tour->price_currency);
        $amount = $this->calculateAmount(
            $tour,
            $travelers,
            $providedAmount !== null ? (float) $providedAmount : null,
            $currency,
        );

        if ($providedAmount !== null && round((float) $providedAmount, 2) !== $amount) {
            throw new BookingAmountMismatchException();
        }

        [$booking, $paymentUrl] = DB::transaction(function () use (
            $payload,
            $bookedByType,
            $bookedBySlug,
            $clientSlug,
            $tour,
            $bookingType,
            $travelers,
            $paymentMode,
            $amount,
            $currency,
        ) {
            $booking = Booking::create([
                'booking_code' => '360TG_' . Str::upper(Str::random(6)),
                'client_slug' => $clientSlug,
                'booked_by_type' => $bookedByType,
                'booked_by_slug' => $bookedBySlug,
                'tour_slug' => $tour->tour_slug,
                'booking_type' => $bookingType,
                'selected_date' => $payload['selectedDate'] ?? $payload['selected_date'],
                'selected_end_date' => $payload['selectedEndDate'] ?? $payload['selected_end_date'] ?? null,
                'travelers' => $travelers,
                'payment_mode' => $paymentMode,
                'payment_status' => ($paymentMode === 'online' || $bookedByType === 'client') ? 'pending' : 'onsite',
                'amount' => $amount,
                'currency' => $currency,
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

            return [$booking, $paymentUrl];
        });

        $booking->load('tour');

        if ($bookedByType === 'client' && $clientSlug) {
            $this->syncBookingHistoryRequest($booking, 'pending');
        }

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
            'selected_end_date' => $payload['selected_end_date'] ?? $payload['selectedEndDate'] ?? null,
            'travelers' => isset($payload['travelers']) ? $travelers : null,
            'lead_traveler' => $payload['leadTraveler'] ?? $payload['lead_traveler'] ?? null,
            'special_requests' => $payload['specialRequests'] ?? $payload['special_requests'] ?? null,
            'dietary_needs' => $payload['dietaryNeeds'] ?? $payload['dietary_needs'] ?? null,
        ], fn ($value) => $value !== null);

        if ($bookingType === 'individual') {
            $updates['group_details'] = null;
            $updates['additional_travelers'] = [];
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

    /** Offline payment received — close the booking request without touching online checkout. */
    public function markRequestCompleted(Booking $booking): array
    {
        $booking->update([
            'payment_status' => 'paid',
            'status' => 'completed',
        ]);

        $booking->load('tour');
        $this->syncBookingHistoryRequest(
            $booking,
            'completed',
            'Payment received. This booking request is completed.',
        );
        $this->notifications->notifyPaymentSuccess($booking);

        return $booking->toBookingArray();
    }

    protected function syncBookingHistoryRequest(Booking $booking, string $status, ?string $adminResponse = null): void
    {
        if (! $booking->client_slug || ! $booking->booking_code) {
            return;
        }

        $lead = is_array($booking->lead_traveler) ? $booking->lead_traveler : [];
        $tourName = $booking->tour?->name ?? $booking->tour_slug;
        $start = $booking->selected_date?->format('M j, Y') ?? '—';
        $end = $booking->selected_end_date?->format('M j, Y');
        $dates = $end ? "{$start} – {$end}" : $start;
        $name = trim(($lead['firstName'] ?? '') . ' ' . ($lead['lastName'] ?? ''));

        $lines = array_filter([
            'Tour booking request',
            'Tour: ' . $tourName,
            'Booking code: ' . $booking->booking_code,
            'Guest: ' . ($name !== '' ? $name : '—'),
            'Email: ' . ($lead['email'] ?? '—'),
            'Phone: ' . ($lead['phone'] ?? '—'),
            'WhatsApp: ' . ($lead['whatsapp'] ?? '—'),
            'Country: ' . ($lead['country'] ?? $lead['nationality'] ?? '—') . (! empty($lead['dialCode']) ? ' (' . $lead['dialCode'] . ')' : ''),
            'Preferred dates: ' . $dates,
            'Adults: ' . (int) ($lead['adults'] ?? $booking->travelers ?? 1),
            'Children: ' . (int) ($lead['children'] ?? 0),
            filled($booking->special_requests) ? 'Notes: ' . $booking->special_requests : null,
        ]);

        $record = InvoiceRequest::query()->where('booking_code', $booking->booking_code)->first();
        $payload = [
            'client_slug' => $booking->client_slug,
            'type' => 'booking',
            'message' => implode("\n", $lines),
            'status' => $status,
            'booking_code' => $booking->booking_code,
        ];

        if ($adminResponse !== null) {
            $payload['admin_response'] = $adminResponse;
        }

        if ($record) {
            $record->update($payload);

            return;
        }

        InvoiceRequest::create([
            'request_uuid' => (string) Str::uuid(),
            ...$payload,
        ]);
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
        $charge = $this->resolvePaystackCharge($tour, (int) $booking->travelers, $amount, $booking->currency);
        $this->syncBookingCharge($booking, $charge);
        $initialized = $this->initializePaystackTransaction($booking, $tour, $charge);

        Payment::create([
            'payment_slug' => (string) Str::uuid(),
            'booking_code' => $booking->booking_code,
            'paystack_reference' => $initialized['reference'],
            'paystack_access_code' => $initialized['access_code'],
            'amount' => $charge['amount'],
            'currency' => $charge['currency'],
            'status' => 'pending',
            'payment_url' => $initialized['authorization_url'],
            'paystack_response' => $initialized['raw'],
        ]);

        return $initialized['authorization_url'];
    }

    protected function reinitializeOnlinePayment(Payment $payment, Booking $booking, Tour $tour, float $amount): string
    {
        $charge = $this->resolvePaystackCharge($tour, (int) $booking->travelers, $amount, $booking->currency);
        $this->syncBookingCharge($booking, $charge);
        $initialized = $this->initializePaystackTransaction($booking, $tour, $charge);

        $payment->update([
            'paystack_reference' => $initialized['reference'],
            'paystack_access_code' => $initialized['access_code'],
            'amount' => $charge['amount'],
            'currency' => $charge['currency'],
            'status' => 'pending',
            'payment_url' => $initialized['authorization_url'],
            'paystack_response' => $initialized['raw'],
            'paid_at' => null,
        ]);

        return $initialized['authorization_url'];
    }

    protected function initializePaystackTransaction(Booking $booking, Tour $tour, array $charge): array
    {
        $email = $booking->lead_traveler['email'] ?? 'customer@360toursghana.com';
        $initialized = $this->paystack->initializeTransaction(
            email: $email,
            amount: $charge['amount'],
            currency: $charge['currency'],
            metadata: [
                'booking_code' => $booking->booking_code,
                'tour_slug' => $tour->tour_slug,
            ]
        );

        Log::info('Paystack initialized', [
            'initialized' => $initialized,
            'currency' => $charge['currency'],
            'amount' => $charge['amount'],
        ]);

        return $initialized;
    }

    protected function resolvePaystackCharge(Tour $tour, int $travelers, ?float $quotedAmount, ?string $requestedCurrency): array
    {
        $requested = $this->paystack->normalizeCurrency($requestedCurrency ?: $tour->price_currency);
        $currency = $this->paystack->resolveChargeCurrency($requested);

        if ($currency !== $requested && ! $this->tourHasPriceInCurrency($tour, $currency)) {
            throw new \RuntimeException(
                'Online payment is not available in ' . $requested . '. Add a ' . $currency . ' price for this tour, or enable ' . $requested . ' on Paystack.'
            );
        }

        if ($currency !== $requested) {
            Log::info('Paystack currency fallback', [
                'from' => $requested,
                'to' => $currency,
                'tour_slug' => $tour->tour_slug,
            ]);
        }

        return [
            'amount' => $this->amountForCurrency($tour, $travelers, $quotedAmount, $requested, $currency),
            'currency' => $currency,
        ];
    }

    protected function syncBookingCharge(Booking $booking, array $charge): void
    {
        if (
            $this->paystack->normalizeCurrency($booking->currency) === $charge['currency']
            && round((float) $booking->amount, 2) === round($charge['amount'], 2)
        ) {
            return;
        }

        $booking->update([
            'amount' => $charge['amount'],
            'currency' => $charge['currency'],
        ]);
    }

    protected function amountForCurrency(
        Tour $tour,
        int $travelers,
        ?float $quotedAmount,
        string $quotedCurrency,
        string $chargeCurrency
    ): float {
        if ($chargeCurrency === $quotedCurrency) {
            return $this->calculateAmount($tour, $travelers, $quotedAmount, $chargeCurrency);
        }

        $quotedFull = $this->fullAmount($tour, $travelers, $quotedCurrency);
        $quotedDeposit = $this->depositAmount($tour, $travelers, $quotedCurrency);
        $quoted = $quotedAmount !== null ? round($quotedAmount, 2) : $quotedFull;
        $isDeposit = $quoted === $quotedDeposit && $quotedDeposit !== $quotedFull;

        return $isDeposit
            ? $this->depositAmount($tour, $travelers, $chargeCurrency)
            : $this->fullAmount($tour, $travelers, $chargeCurrency);
    }

    protected function fullAmount(Tour $tour, int $travelers, string $currency): float
    {
        return round($this->resolveTourUnitPrice($tour, $currency) * $travelers, 2);
    }

    protected function depositAmount(Tour $tour, int $travelers, string $currency): float
    {
        $settings = $tour->booking_settings ?? [];
        $depositPercent = max(1, min(100, (int) ($settings['depositPercent'] ?? 100)));

        return round($this->fullAmount($tour, $travelers, $currency) * ($depositPercent / 100), 2);
    }

    protected function tourHasPriceInCurrency(Tour $tour, string $currency): bool
    {
        $currency = $this->paystack->normalizeCurrency($currency);

        if ($currency === 'USD') {
            return (float) ($tour->price_amount_usd ?? 0) > 0
                || ($this->paystack->normalizeCurrency($tour->price_currency) === 'USD' && (float) $tour->price_amount > 0);
        }

        if ($currency === 'GHS') {
            return (float) ($tour->price_amount_ghs ?? 0) > 0
                || ($this->paystack->normalizeCurrency($tour->price_currency) === 'GHS' && (float) $tour->price_amount > 0);
        }

        return $this->resolveTourUnitPrice($tour, $currency) > 0;
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

        $this->reinitializeOnlinePayment($payment, $booking, $booking->tour, (float) $booking->amount);

        $booking->update([
            'payment_status' => 'pending',
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

        $payload['groupDetails'] = null;
        $payload['group_details'] = null;
        $payload['additionalTravelers'] = [];
        $payload['additional_travelers'] = [];

        return $payload;
    }

    protected function calculateAmount(Tour $tour, int $travelers, ?float $providedAmount = null, ?string $currency = null): float
    {
        $currency = $this->paystack->normalizeCurrency($currency ?: $tour->price_currency);
        $base = $this->fullAmount($tour, $travelers, $currency);
        $depositAmount = $this->depositAmount($tour, $travelers, $currency);

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
        $currency = $this->paystack->normalizeCurrency($currency ?: $tour->price_currency);

        if ($currency === 'USD') {
            $usd = (float) ($tour->price_amount_usd ?? 0);

            return $usd > 0 ? $usd : (float) $tour->price_amount;
        }

        $ghs = (float) ($tour->price_amount_ghs ?? 0);

        return $ghs > 0 ? $ghs : (float) $tour->price_amount;
    }
}
