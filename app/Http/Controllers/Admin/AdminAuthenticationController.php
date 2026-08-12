<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Http\Requests\Admin\UpdateProfileRequest;
use App\Http\Requests\Auth\EmailOrPhoneRequest;
use App\Http\Requests\Auth\ResendOtpRequest;
use App\Http\Requests\Auth\VerifyOtpRequest;
use App\Models\Admin;
use Illuminate\Http\JsonResponse;

class AdminAuthenticationController extends Controller
{
    public function login(EmailOrPhoneRequest $request): JsonResponse
    {
        $admin = self::findActorByEmailOrPhone(Admin::class, $request->validated('emailOrPhone'));

        return self::sendActorOtp($admin, 'admin', 'login');
    }

    public function verifyOtp(VerifyOtpRequest $request): JsonResponse
    {
        $data = $request->validated();
        $admin = self::findActorByEmailOrPhone(Admin::class, $data['emailOrPhone']);

        return self::verifyActorOtp(
            otp: $data['otp'],
            actor: $admin,
            guard: 'admin',
        );
    }

    public function resendOtp(ResendOtpRequest $request): JsonResponse
    {
        $admin = self::findActorByEmailOrPhone(Admin::class, $request->validated('emailOrPhone'));

        if (! $admin) {
            return self::sendActorOtp(null, 'admin', 'login');
        }

        $type = self::resolveActorOtpType($admin, 'admin');

        return self::sendActorOtp($admin, 'admin', $type);
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
            'admin',
            $data
        );
    }
}
