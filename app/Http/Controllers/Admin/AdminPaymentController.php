<?php

namespace App\Http\Controllers\Admin;

use App\Exceptions\BookingAmountMismatchException;
use App\Http\Controllers\Controller;
use App\Models\Booking;
use App\Models\Payment;
use App\Services\BookingService;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class AdminPaymentController extends Controller
{
    public function __construct(protected BookingService $bookingService) {}

    public function index(Request $request): JsonResponse
    {
        $query = Payment::query()
            ->with(['booking.tour', 'booking.client', 'booking.admin'])
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

        $this->applyAdminFilter($query, $request);
        $this->applyClientFilter($query, $request);

        if ($request->filled('booking_code') || $request->filled('bookingCode')) {
            $bookingCode = $request->input('bookingCode', $request->input('booking_code'));
            $query->where('booking_code', $bookingCode);
        }

        $paginator = self::paginateQuery($request, $query);

        return self::paginatedApiResponse('Payments retrieved', $paginator, fn (Payment $payment) => $payment->toPaymentArray());
    }

    public function show(Payment $payment): JsonResponse
    {
        $payment->load(['booking.tour', 'booking.client', 'booking.admin']);

        return self::apiResponse(false, 'Action Successful', (string) self::API_SUCCESS, 'Payment retrieved', $payment->toPaymentArray());
    }

    public function store(Request $request): JsonResponse
    {
        $request->validate([
            'bookingCode' => 'required_without:booking_code|string|exists:bookings,booking_code',
            'booking_code' => 'required_without:bookingCode|string|exists:bookings,booking_code',
            'amount' => 'nullable|numeric|min:0',
        ]);

        $bookingCode = $request->input('bookingCode', $request->input('booking_code'));
        $booking = Booking::query()->where('booking_code', $bookingCode)->firstOrFail();

        try {
            $result = $this->bookingService->recordOnsitePayment(
                $booking,
                $request->input('amount') !== null ? (float) $request->input('amount') : null
            );
        } catch (BookingAmountMismatchException $e) {
            return self::apiResponse(true, 'Action Unsuccessful', (string) self::API_BAD_REQUEST, $e->getMessage(), []);
        } catch (\RuntimeException $e) {
            return self::apiResponse(true, 'Action Unsuccessful', (string) self::API_FAIL, $e->getMessage(), []);
        }

        return self::apiResponse(false, 'Action Successful', (string) self::API_CREATED, 'Offline payment recorded', $result);
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

    protected function applyAdminFilter(Builder $query, Request $request): void
    {
        if ($request->filled('admin_slug') || $request->filled('adminSlug')) {
            $adminSlug = $request->input('adminSlug', $request->input('admin_slug'));
            $query->whereHas('booking', fn (Builder $bookingQuery) => $bookingQuery->where('admin_slug', $adminSlug));

            return;
        }

        $adminSearch = $this->searchTerm($request, ['admin', 'adminName', 'admin_name']);

        if (! $adminSearch) {
            return;
        }

        $query->whereHas('booking.admin', function (Builder $adminQuery) use ($adminSearch) {
            $adminQuery->where(function (Builder $nameQuery) use ($adminSearch) {
                $term = '%' . $adminSearch . '%';
                $nameQuery->where('first_name', 'like', $term)
                    ->orWhere('last_name', 'like', $term)
                    ->orWhere('email', 'like', $term)
                    ->orWhereRaw("CONCAT(COALESCE(first_name, ''), ' ', COALESCE(last_name, '')) LIKE ?", [$term]);
            });
        });
    }

    protected function applyClientFilter(Builder $query, Request $request): void
    {
        if ($request->filled('client_slug') || $request->filled('clientSlug')) {
            $clientSlug = $request->input('clientSlug', $request->input('client_slug'));
            $query->whereHas('booking', fn (Builder $bookingQuery) => $bookingQuery->where('client_slug', $clientSlug));

            return;
        }

        $clientSearch = $this->searchTerm($request, ['client', 'clientName', 'client_name']);

        if (! $clientSearch) {
            return;
        }

        $query->whereHas('booking.client', function (Builder $clientQuery) use ($clientSearch) {
            $clientQuery->where(function (Builder $nameQuery) use ($clientSearch) {
                $term = '%' . $clientSearch . '%';
                $nameQuery->where('first_name', 'like', $term)
                    ->orWhere('last_name', 'like', $term)
                    ->orWhere('email', 'like', $term)
                    ->orWhere('phone_number', 'like', $term)
                    ->orWhereRaw("CONCAT(COALESCE(first_name, ''), ' ', COALESCE(last_name, '')) LIKE ?", [$term]);
            });
        });
    }

    protected function searchTerm(Request $request, array $keys): ?string
    {
        foreach ($keys as $key) {
            $value = trim((string) $request->input($key, ''));

            if ($value !== '') {
                return $value;
            }
        }

        return null;
    }
}
