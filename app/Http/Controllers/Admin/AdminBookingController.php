<?php

namespace App\Http\Controllers\Admin;

use App\Exceptions\BookingAmountMismatchException;
use App\Http\Controllers\Controller;
use App\Http\Requests\Admin\AdminBookingRules;
use App\Models\Booking;
use App\Models\Client;
use App\Services\BookingService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class AdminBookingController extends Controller
{
    public function __construct(protected BookingService $bookingService) {}

    public function index(Request $request): JsonResponse
    {
        $query = Booking::query()->with('tour', 'client', 'admin');

        if ($request->filled('client_slug') || $request->filled('clientSlug')) {
            $query->where('client_slug', $request->input('clientSlug', $request->input('client_slug')));
        }

        if ($request->filled('admin_slug') || $request->filled('adminSlug')) {
            $query->where('admin_slug', $request->input('adminSlug', $request->input('admin_slug')));
        }

        if ($request->filled('booked_by')) {
            if ($request->booked_by === 'admin') {
                $query->where('booked_by_type', 'admin');
            }

            if ($request->booked_by === 'client') {
                $query->where('booked_by_type', 'client');
            }
        }

        $paginator = self::paginateQuery($request, $query->latest());

        return self::paginatedApiResponse('Bookings retrieved', $paginator, fn(Booking $b) => $b->toBookingArray());
    }

    public function show(Booking $booking): JsonResponse
    {
        $booking->load('tour', 'client', 'admin');

        return self::apiResponse(false, 'Action Successful', (string) self::API_SUCCESS, 'Booking retrieved', $booking->toBookingArray());
    }

    public function store(Request $request): JsonResponse
    {
        $bookingType = $request->input('bookingType', $request->input('booking_type', 'group'));

        $request->validate(AdminBookingRules::store($bookingType));

        $admin = request()->user();
        $clientSlug = $request->input('clientSlug') ?? $request->input('client_slug');

        if ($clientSlug && ! Client::query()->where('client_slug', $clientSlug)->exists()) {
            return self::apiResponse(true, 'Action Unsuccessful', (string) self::API_NOT_FOUND, 'Client not found', []);
        }

        try {
            $result = $this->bookingService->create(
                $request->all(),
                'admin',
                $admin->admin_slug,
                $clientSlug
            );
        } catch (BookingAmountMismatchException $e) {
            return self::apiResponse(true, 'Action Unsuccessful', (string) self::API_BAD_REQUEST, $e->getMessage(), []);
        }

        return self::apiResponse(false, 'Action Successful', (string) self::API_CREATED, 'Booking created', $result);
    }

    public function update(Request $request, Booking $booking): JsonResponse
    {
        if ($booking->booked_by_type !== 'admin') {
            return self::apiResponse(true, 'Action Unsuccessful', (string) self::API_FAIL, 'Only manually created bookings can be edited', []);
        }

        $bookingType = $request->input('bookingType', $request->input('booking_type', $booking->booking_type));

        $request->validate(AdminBookingRules::update($bookingType));

        try {
            $result = $this->bookingService->updateAdminBooking($booking, $request->all());
        } catch (BookingAmountMismatchException $e) {
            return self::apiResponse(true, 'Action Unsuccessful', (string) self::API_BAD_REQUEST, $e->getMessage(), []);
        } catch (\RuntimeException $e) {
            return self::apiResponse(true, 'Action Unsuccessful', (string) self::API_FAIL, $e->getMessage(), []);
        }

        return self::apiResponse(false, 'Action Successful', (string) self::API_SUCCESS, 'Booking updated', $result);
    }

    public function destroy(Booking $booking): JsonResponse
    {
        if ($booking->booked_by_type !== 'admin') {
            return self::apiResponse(true, 'Action Unsuccessful', (string) self::API_FAIL, 'Only manually created bookings can be deleted', []);
        }

        $booking->delete();

        return self::apiResponse(false, 'Action Successful', (string) self::API_SUCCESS, 'Booking deleted', []);
    }
}
