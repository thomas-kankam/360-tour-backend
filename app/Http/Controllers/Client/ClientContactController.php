<?php

namespace App\Http\Controllers\Client;

use App\Http\Controllers\Controller;
use App\Models\Contact;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class ClientContactController extends Controller
{
    public function index(Request $request): JsonResponse
    {
        $client = request()->user();

        $query = Contact::query()
            ->where(function ($builder) use ($client) {
                $builder->where('client_slug', $client->client_slug)
                    ->orWhere('email', $client->email);
            })
            ->latest();

        $paginator = self::paginateQuery($request, $query);

        return self::paginatedApiResponse('Enquiries retrieved', $paginator, fn (Contact $contact) => $contact->toEnquiryArray());
    }

    public function show(Contact $enquiry): JsonResponse
    {
        $client = request()->user();

        if ($enquiry->client_slug !== $client->client_slug && $enquiry->email !== $client->email) {
            return self::apiResponse(true, 'Action Unsuccessful', (string) self::API_NOT_FOUND, 'Enquiry not found', []);
        }

        return self::apiResponse(false, 'Action Successful', (string) self::API_SUCCESS, 'Enquiry retrieved', $enquiry->toEnquiryArray());
    }
}
