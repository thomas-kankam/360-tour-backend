<?php

namespace App\Http\Controllers\Client;

use App\Http\Controllers\Controller;
use App\Http\Requests\Auth\EmailOrPhoneRequest;
use App\Http\Requests\Auth\ResendOtpRequest;
use App\Http\Requests\Auth\VerifyOtpRequest;
use App\Http\Requests\Client\ClientRegisterRequest;
use App\Http\Requests\Client\UpdateProfileRequest;
use App\Models\Client;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Str;

class ClientAuthenticationController extends Controller
{
    public function login(EmailOrPhoneRequest $request): JsonResponse
    {
        $client = self::findActorByEmailOrPhone(Client::class, $request->validated('emailOrPhone'));

        return self::sendActorOtp($client, 'client', 'login');
    }

    public function register(ClientRegisterRequest $request): JsonResponse
    {
        $data = $request->validated();
        $data['profile_image'] = static::base64ImageDecode($data['profile_image'] ?? null);

        $client = Client::create([
            'client_slug' => (string) Str::uuid(),
            'first_name' => $data['first_name'],
            'last_name' => $data['last_name'] ?? null,
            'phone_number' => $data['phone_number'] ?? null,
            'email' => $data['email'],
            'location' => $data['location'] ?? null,
            'status' => 'inactive',
            'is_verified' => false,
            'profile_image' => $data['profile_image'],
        ]);

        return self::sendActorOtp($client, 'client', 'registration');
    }

    public function verifyOtp(VerifyOtpRequest $request): JsonResponse
    {
        $data = $request->validated();
        $client = self::findActorByEmailOrPhone(Client::class, $data['emailOrPhone']);

        return self::verifyActorOtp(
            otp: $data['otp'],
            actor: $client,
            guard: 'client',
        );
    }

    public function resendOtp(ResendOtpRequest $request): JsonResponse
    {
        $client = self::findActorByEmailOrPhone(Client::class, $request->validated('emailOrPhone'));

        if (! $client) {
            return self::sendActorOtp(null, 'client', 'login');
        }

        $type = self::resolveActorOtpType($client, 'client');

        return self::sendActorOtp($client, 'client', $type);
    }

    public function logout(): JsonResponse
    {
        request()->user()->token()->revoke();

        return self::apiResponse(
            in_error: false,
            message: 'Action Successful',
            reason: 'Logout successful',
            status_code: (string) self::API_SUCCESS,
            data: []
        );
    }

    public function updateProfile(UpdateProfileRequest $request): JsonResponse
    {
        $data = collect($request->validated())->except(['phone_number'])->all();

        if (isset($data['profile_image'])) {
            $data['profile_image'] = static::base64ImageDecode($data['profile_image']) ?? $data['profile_image'];
        }

        return self::updateActorProfile(
            request()->user(),
            'client',
            $data
        );
    }
}
