<?php

namespace App\Http\Controllers\Client;

use App\Exceptions\BookingAmountMismatchException;
use App\Http\Controllers\Controller;
use App\Http\Requests\Client\ClientBookingRules;
use App\Models\Booking;
use App\Services\BookingService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class ClientBookingController extends Controller
{
    public function __construct(protected BookingService $bookingService) {}

    public function index(Request $request): JsonResponse
    {
        $client = request()->user();
        $paginator = self::paginateQuery(
            $request,
            Booking::query()
                ->with('tour')
                ->where('client_slug', $client->client_slug)
                ->latest()
        );

        return self::paginatedApiResponse('Bookings retrieved', $paginator, fn(Booking $booking) => $booking->toBookingArray());
    }

    public function show(Booking $booking): JsonResponse
    {
        $this->authorizeClientBooking($booking);

        $booking->load('tour');

        return self::apiResponse(false, 'Action Successful', (string) self::API_SUCCESS, 'Booking retrieved', $booking->toBookingArray());
    }

    public function store(Request $request): JsonResponse
    {
        $bookingType = $request->input('bookingType', $request->input('booking_type', 'group'));

        $request->validate(ClientBookingRules::store($bookingType));

        $client = request()->user();

        try {
            $result = $this->bookingService->create(
                $request->all(),
                'client',
                $client->client_slug,
                $client->client_slug
            );
        } catch (BookingAmountMismatchException $e) {
            return self::apiResponse(true, 'Action Unsuccessful', (string) self::API_BAD_REQUEST, $e->getMessage(), []);
        }

        return self::apiResponse(false, 'Action Successful', (string) self::API_CREATED, 'Booking submitted', $result);
    }

    public function update(Request $request, Booking $booking): JsonResponse
    {
        $this->authorizeClientBooking($booking);

        $bookingType = $request->input('bookingType', $request->input('booking_type', $booking->booking_type));

        $request->validate(ClientBookingRules::update($bookingType));

        try {
            $result = $this->bookingService->updateClientBooking($booking, $request->all());
        } catch (BookingAmountMismatchException $e) {
            return self::apiResponse(true, 'Action Unsuccessful', (string) self::API_BAD_REQUEST, $e->getMessage(), []);
        } catch (\RuntimeException $e) {
            return self::apiResponse(true, 'Action Unsuccessful', (string) self::API_FAIL, $e->getMessage(), []);
        }

        return self::apiResponse(false, 'Action Successful', (string) self::API_SUCCESS, 'Booking updated', $result);
    }

    public function destroy(Booking $booking): JsonResponse
    {
        $this->authorizeClientBooking($booking);
        $booking->delete();

        return self::apiResponse(false, 'Action Successful', (string) self::API_SUCCESS, 'Booking deleted', []);
    }

    protected function authorizeClientBooking(Booking $booking): void
    {
        if ($booking->client_slug !== request()->user()->client_slug) {
            abort(403, 'Booking not found');
        }
    }
}
