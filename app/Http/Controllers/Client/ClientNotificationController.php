<?php

namespace App\Http\Controllers\Client;

use App\Http\Controllers\NotificationControllerBase;
use Illuminate\Http\Request;

class ClientNotificationController extends NotificationControllerBase
{
    protected function recipientType(): string
    {
        return 'client';
    }

    protected function recipientSlug(Request $request): string
    {
        return $request->user()->client_slug;
    }
}
