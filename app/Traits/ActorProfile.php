<?php

namespace App\Traits;

use App\Models\Actor;
use App\Models\Admin;
use Illuminate\Http\JsonResponse;

trait ActorProfile
{
    use ApiTransformer, Helpers;

    protected static function updateActorProfile(Actor $actor, string $guard, array $data): JsonResponse
    {
        $actor->update($data);

        $responseData = $actor instanceof Admin
            ? $actor->fresh(['role.permissions'])->toAuthArray()
            : $actor->fresh()->toArray();

        return self::apiResponse(
            in_error: false,
            message: 'Action Successful',
            reason: 'Profile updated successfully',
            status_code: (string) self::API_SUCCESS,
            data: $responseData
        );
    }
}
