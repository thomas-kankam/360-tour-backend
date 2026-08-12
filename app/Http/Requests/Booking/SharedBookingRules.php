<?php

namespace App\Http\Requests\Booking;

class SharedBookingRules
{
    public static function commonRules(bool $required): array
    {
        $rule = $required ? 'required' : 'sometimes';

        return [
            'bookingType' => ($required ? 'required' : 'sometimes') . '|in:group,individual',
            'leadTraveler' => $rule . '|array|min:1',
            'leadTraveler.firstName' => $rule . '|string|max:255',
            'leadTraveler.lastName' => $rule . '|string|max:255',
            'leadTraveler.email' => $rule . '|email',
            'leadTraveler.phone' => 'nullable|string|max:50',
            'leadTraveler.nationality' => 'nullable|string|max:255',
            'specialRequests' => 'nullable|string',
            'dietaryNeeds' => 'nullable|string',
        ];
    }

    public static function typeRules(string $bookingType, bool $required): array
    {
        if ($bookingType === 'individual') {
            return [
                'travelers' => ($required ? 'required' : 'sometimes') . '|integer|in:1',
                'groupDetails' => 'prohibited',
                'group_details' => 'prohibited',
                'additionalTravelers' => 'prohibited',
                'additional_travelers' => 'prohibited',
            ];
        }

        $groupRule = $required ? 'required' : 'sometimes';

        return [
            'travelers' => ($required ? 'required' : 'sometimes') . '|integer|min:2',
            'groupDetails' => $groupRule . '|array',
            'groupDetails.groupName' => $groupRule . '|string|max:255',
            'groupDetails.groupType' => $groupRule . '|string|max:255',
            'groupDetails.organization' => 'nullable|string|max:255',
            'additionalTravelers' => 'nullable|array',
            'additionalTravelers.*.firstName' => 'nullable|string|max:255',
            'additionalTravelers.*.lastName' => 'nullable|string|max:255',
            'additionalTravelers.*.email' => 'nullable|email',
        ];
    }
}
