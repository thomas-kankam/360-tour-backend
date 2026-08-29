<?php

namespace App\Jobs;

use App\Mail\NotificationEmail;
use App\Models\Admin;
use App\Models\Client;
use App\Models\UserNotification;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Mail;

class SendNotificationEmailJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public function __construct(public string $notificationUuid) {}

    public function handle(): void
    {
        $notification = UserNotification::query()
            ->where('notification_uuid', $this->notificationUuid)
            ->first();

        if (! $notification) {
            return;
        }

        $email = match ($notification->recipient_type) {
            'client' => Client::query()->where('client_slug', $notification->recipient_slug)->value('email'),
            'admin' => Admin::query()->where('admin_slug', $notification->recipient_slug)->value('email'),
            default => null,
        };

        if (! $email) {
            return;
        }

        Mail::to($email)->send(new NotificationEmail($notification));
    }
}
