<?php

namespace App\Http\Controllers\Client;

use App\Http\Controllers\Controller;
use App\Http\Requests\Auth\EmailOrPhoneRequest;
use App\Models\Client;
use Illuminate\Http\JsonResponse;

class ClientPasswordController extends Controller
{
    public function resendOtp(EmailOrPhoneRequest $request): JsonResponse
    {
        $client = self::findActorByEmailOrPhone(Client::class, $request->validated('emailOrPhone'));

        if (! $client) {
            return self::sendActorOtp(null, 'client', 'login');
        }

        $type = $client->is_verified ? 'login' : 'registration';

        return self::sendActorOtp($client, 'client', $type);
    }
}
