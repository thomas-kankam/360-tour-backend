<?php

namespace App\Http\Controllers;

use App\Models\Contact;
use App\Models\Tour;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class ContactController extends Controller
{
    public function store(Request $request): JsonResponse
    {
        $data = $request->validate([
            'fullname' => 'required|string|max:255',
            'email' => 'required|email',
            'phone_number' => 'nullable|string',
            'message' => 'required|string',
            'type' => 'nullable|string',
        ]);

        $clientSlug = null;
        if ($request->user('client-api')) {
            $clientSlug = $request->user('client-api')->client_slug;
        }

        $contact = Contact::create([
            'fullname' => $data['fullname'],
            'email' => $data['email'],
            'phone_number' => $data['phone_number'] ?? null,
            'message' => $data['message'],
            'type' => $data['type'] ?? 'general',
            'status' => 'new',
            'client_slug' => $clientSlug,
        ]);

        return self::apiResponse(false, 'Action Successful', (string) self::API_CREATED, 'Contact submitted', $contact->toEnquiryArray());
    }
}
