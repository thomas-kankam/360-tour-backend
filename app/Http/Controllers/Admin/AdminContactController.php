<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Contact;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class AdminContactController extends Controller
{
    public function index(Request $request): JsonResponse
    {
        $paginator = self::paginateQuery($request, Contact::query()->latest());

        return self::paginatedApiResponse('Contacts retrieved', $paginator, fn (Contact $contact) => $contact->toArray());
    }

    public function show(Contact $contact): JsonResponse
    {
        return self::apiResponse(false, 'Action Successful', (string) self::API_SUCCESS, 'Contact retrieved', $contact->toArray());
    }

    public function update(Request $request, Contact $contact): JsonResponse
    {
        $contact->update($request->only('status'));

        return self::apiResponse(false, 'Action Successful', (string) self::API_SUCCESS, 'Contact updated', $contact->fresh()->toArray());
    }

    public function destroy(Contact $contact): JsonResponse
    {
        $contact->delete();

        return self::apiResponse(false, 'Action Successful', (string) self::API_SUCCESS, 'Contact deleted', []);
    }
}
