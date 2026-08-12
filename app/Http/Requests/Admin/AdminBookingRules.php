<?php

namespace App\Http\Requests\Admin;

use App\Http\Requests\Booking\SharedBookingRules;

class AdminBookingRules
{
    public static function store(string $bookingType): array
    {
        return array_merge(SharedBookingRules::commonRules(required: true), SharedBookingRules::typeRules($bookingType, required: true), [
            'tourSlug' => 'required|string|exists:tours,tour_slug',
            'selectedDate' => 'required|date',
            'paymentMode' => 'required|in:online,onsite',
            'amount' => 'required|numeric|min:0',
            'clientSlug' => 'nullable|string|exists:clients,client_slug',
            'client_slug' => 'nullable|string|exists:clients,client_slug',
        ]);
    }

    public static function update(string $bookingType): array
    {
        return array_merge(SharedBookingRules::commonRules(required: false), SharedBookingRules::typeRules($bookingType, required: false), [
            'selectedDate' => 'sometimes|date',
            'selected_date' => 'sometimes|date',
            'paymentMode' => 'sometimes|in:online,onsite',
            'amount' => 'required_if:paymentMode,online|nullable|numeric|min:0',
            'special_requests' => 'nullable|string',
            'dietary_needs' => 'nullable|string',
            'additional_travelers' => 'nullable|array',
            'specialRequests' => 'nullable|string',
            'dietaryNeeds' => 'nullable|string',
            'status' => 'sometimes|string|in:pending,confirmed,cancelled',
            'payment_status' => 'sometimes|string|in:pending,paid,failed,onsite',
            'paymentStatus' => 'sometimes|string|in:pending,paid,failed,onsite',
        ]);
    }
}
