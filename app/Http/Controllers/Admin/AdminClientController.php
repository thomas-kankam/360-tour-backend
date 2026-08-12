<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Client;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Str;

class AdminClientController extends Controller
{
    public function index(Request $request): JsonResponse
    {
        $paginator = self::paginateQuery($request, Client::query()->latest());

        return self::paginatedApiResponse('Clients retrieved', $paginator, fn (Client $client) => $client->toArray());
    }

    public function show(Client $client): JsonResponse
    {
        return self::apiResponse(false, 'Action Successful', (string) self::API_SUCCESS, 'Client retrieved', $client->toArray());
    }

    public function store(Request $request): JsonResponse
    {
        $data = $request->validate([
            'first_name' => 'required|string',
            'email' => 'required|email|unique:clients,email',
            'last_name' => 'nullable|string',
            'phone_number' => 'nullable|string',
            'location' => 'nullable|string',
        ]);

        $client = Client::create([
            'client_slug' => (string) Str::uuid(),
            ...$data,
            'status' => 'active',
            'is_verified' => true,
            'verified_at' => now(),
        ]);

        return self::apiResponse(false, 'Action Successful', (string) self::API_CREATED, 'Client created', $client->toArray());
    }

    public function update(Request $request, Client $client): JsonResponse
    {
        $client->update($request->only(['first_name', 'last_name', 'phone_number', 'email', 'location', 'status']));

        return self::apiResponse(false, 'Action Successful', (string) self::API_SUCCESS, 'Client updated', $client->fresh()->toArray());
    }

    public function destroy(Client $client): JsonResponse
    {
        $client->delete();

        return self::apiResponse(false, 'Action Successful', (string) self::API_SUCCESS, 'Client deleted', []);
    }
}
