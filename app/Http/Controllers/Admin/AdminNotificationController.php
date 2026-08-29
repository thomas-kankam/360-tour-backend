<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\NotificationControllerBase;
use Illuminate\Http\Request;

class AdminNotificationController extends NotificationControllerBase
{
    protected function recipientType(): string
    {
        return 'admin';
    }

    protected function recipientSlug(Request $request): string
    {
        return $request->user()->admin_slug;
    }
}
