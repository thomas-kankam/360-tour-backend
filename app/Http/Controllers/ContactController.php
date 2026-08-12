<?php

namespace App\Http\Controllers;

use App\Models\Contact;
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

        $contact = Contact::create([
            'fullname' => $data['fullname'],
            'email' => $data['email'],
            'phone_number' => $data['phone_number'] ?? null,
            'message' => $data['message'],
            'type' => $data['type'] ?? 'general',
            'status' => 'new',
        ]);

        return self::apiResponse(false, 'Action Successful', (string) self::API_CREATED, 'Contact submitted', $contact->toArray());
    }
}
