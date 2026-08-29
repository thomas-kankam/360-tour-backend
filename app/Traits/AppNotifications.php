<?php

namespace App\Traits;

use App\Jobs\SendOtpEmailJob;
use App\Models\Actor;
use App\Models\Otp;
use Illuminate\Http\JsonResponse;

trait AppNotifications
{
    use ApiTransformer, Helpers;

    protected static function isVerifiableActorVerified(?Actor $actor, string $guard): bool
    {
        if ($guard === 'client' && $actor instanceof \App\Models\Client) {
            return (bool) $actor->is_verified || $actor->verified_at !== null;
        }

        return false;
    }

    protected static function isClientVerified(?Actor $actor): bool
    {
        return self::isVerifiableActorVerified($actor, 'client');
    }

    protected static function resolveActorOtpType(?Actor $actor, string $guard): string
    {
        if ($actor && $guard === 'client' && ! self::isVerifiableActorVerified($actor, $guard)) {
            return 'registration';
        }

        return 'login';
    }

    protected static function checkOtp(string $guard, string $otp, int $actor_id): array
    {
        $otpValue = self::normalizeOtp($otp);

        $validOtp = Otp::query()
            ->where('actor_id', $actor_id)
            ->where('guard', $guard)
            ->where('token', $otpValue)
            ->where('expires_at', '>', now())
            ->latest('id')
            ->first();

        if ($validOtp) {
            return [
                'status' => 'valid',
                'channel' => $validOtp->channel,
                'otp' => $validOtp,
                'type' => $validOtp->type,
            ];
        }

        $expiredOtp = Otp::query()
            ->where('actor_id', $actor_id)
            ->where('guard', $guard)
            ->where('token', $otpValue)
            ->where('expires_at', '<=', now())
            ->exists();

        if ($expiredOtp) {
            return ['status' => 'expired'];
        }

        return ['status' => 'not_found'];
    }

    public static function sendActorOtp(?Actor $actor, string $guard, string $type): JsonResponse
    {
        if (! $actor) {
            return self::apiResponse(
                in_error: true,
                message: 'Action Unsuccessful',
                reason: 'User cannot be found',
                status_code: (string) self::API_NOT_FOUND,
                data: []
            );
        }

        $otp = self::otpCode(
            type: $type,
            actor_id: $actor->id,
            channel: 'email',
            guard: $guard
        );

        SendOtpEmailJob::dispatch($actor->email, (int) $otp, $type);

        return self::apiResponse(
            in_error: false,
            message: 'Action Successful',
            reason: 'Otp sent to email successfully',
            status_code: (string) self::API_SUCCESS,
            data: array_merge($actor->toArray(), ['otp_type' => $type])
        );
    }

    protected static function verifyActorOtp(string $otp, ?Actor $actor, string $guard): JsonResponse
    {
        if (! $actor) {
            return self::apiResponse(
                in_error: true,
                message: 'Action Unsuccessful',
                reason: 'User account cannot be found',
                status_code: (string) self::API_NOT_FOUND,
                data: []
            );
        }

        $actor->refresh();

        $result = self::checkOtp(guard: $guard, otp: self::normalizeOtp($otp), actor_id: $actor->id);

        if ($result['status'] === 'expired') {
            return self::apiResponse(
                in_error: true,
                message: 'Action Unsuccessful',
                reason: 'Otp expired',
                status_code: (string) self::API_FAIL,
                data: []
            );
        }

        if ($result['status'] === 'not_found') {
            return self::apiResponse(
                in_error: true,
                message: 'Action Unsuccessful',
                reason: 'Otp not found',
                status_code: (string) self::API_NOT_FOUND,
                data: []
            );
        }

        $verifiedType = $result['type'];
        $shouldActivate = $guard === 'client'
            && $verifiedType === 'registration'
            && ! self::isVerifiableActorVerified($actor, $guard);

        $accessToken = self::apiToken($actor, $guard);

        if ($shouldActivate) {
            $actor->is_verified = true;
            $actor->verified_at = now();
            $actor->status = 'active';
            $actor->save();

            if ($guard === 'client' && $actor instanceof \App\Models\Client) {
                $clientName = trim($actor->first_name . ' ' . ($actor->last_name ?? '')) ?: $actor->email;
                app(\App\Services\NotificationService::class)->notifyAllAdmins(
                    type: \App\Models\UserNotification::TYPE_CLIENT_REGISTERED,
                    title: 'New client signed up',
                    body: $clientName . ' verified their account.',
                    actionUrl: \App\Services\NotificationService::adminBaseUrl() . '/admin/clients/' . $actor->client_slug,
                    meta: ['client_slug' => $actor->client_slug],
                );
            }
        }

        $reason = $shouldActivate
            ? 'Account verified successfully'
            : 'Login successful';

        $responseData = $guard === 'admin' && $actor instanceof \App\Models\Admin
            ? $actor->fresh(['role.permissions'])->toAuthArray()
            : $actor->fresh()->toArray();

        $responseData['token'] = $accessToken;

        return self::apiResponse(
            in_error: false,
            message: 'Action Successful',
            reason: $reason,
            status_code: (string) self::API_SUCCESS,
            data: $responseData
        );
    }
}
