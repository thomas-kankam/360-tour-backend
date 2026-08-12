<?php

namespace App\Http\Controllers\Client;

use App\Http\Controllers\Controller;
use App\Models\Payment;
use App\Services\BookingService;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class ClientPaymentController extends Controller
{
    public function __construct(protected BookingService $bookingService) {}

    public function index(Request $request): JsonResponse
    {
        $client = request()->user();
        $query = Payment::query()
            ->with(['booking.tour'])
            ->whereHas('booking', fn (Builder $bookingQuery) => $bookingQuery->where('client_slug', $client->client_slug))
            ->latest();

        if ($request->filled('status')) {
            $status = Payment::statusFilterValue((string) $request->status);

            if ($status) {
                $query->where('status', $status);
            }
        }

        $paymentMode = $this->resolvePaymentModeFilter($request);

        if ($paymentMode) {
            $query->whereHas('booking', fn (Builder $bookingQuery) => $bookingQuery->where('payment_mode', $paymentMode));
        }

        $paginator = self::paginateQuery($request, $query);

        return self::paginatedApiResponse('Payments retrieved', $paginator, fn (Payment $payment) => $payment->toPaymentArray());
    }

    public function show(Payment $payment): JsonResponse
    {
        $this->authorizeClientPayment($payment);
        $payment->load(['booking.tour']);

        return self::apiResponse(false, 'Action Successful', (string) self::API_SUCCESS, 'Payment retrieved', $payment->toPaymentArray());
    }

    public function retry(Payment $payment): JsonResponse
    {
        $this->authorizeClientPayment($payment);

        try {
            $result = $this->bookingService->retryClientPayment($payment);
        } catch (\RuntimeException $e) {
            return self::apiResponse(true, 'Action Unsuccessful', (string) self::API_FAIL, $e->getMessage(), []);
        }

        return self::apiResponse(false, 'Action Successful', (string) self::API_SUCCESS, 'Payment retry initialized', $result);
    }

    protected function authorizeClientPayment(Payment $payment): void
    {
        $payment->loadMissing('booking');

        if ($payment->booking?->client_slug !== request()->user()->client_slug) {
            abort(403, 'Payment not found');
        }
    }

    protected function resolvePaymentModeFilter(Request $request): ?string
    {
        $paymentMode = $request->input('paymentMode', $request->input('payment_mode'));
        $bookingType = $request->input('bookingType', $request->input('booking_type'));

        if (in_array($bookingType, ['online', 'onsite'], true)) {
            return $bookingType;
        }

        return in_array($paymentMode, ['online', 'onsite'], true) ? $paymentMode : null;
    }
}
