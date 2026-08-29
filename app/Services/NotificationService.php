<?php

namespace App\Services;

use App\Jobs\SendNotificationEmailJob;
use App\Models\Admin;
use App\Models\UserNotification;
use Illuminate\Support\Str;

class NotificationService
{
    public function notifyClient(
        string $clientSlug,
        string $type,
        string $title,
        ?string $body = null,
        ?string $actionUrl = null,
        array $meta = [],
        bool $sendEmail = true,
    ): UserNotification {
        return $this->create('client', $clientSlug, $type, $title, $body, $actionUrl, $meta, $sendEmail);
    }

    public function notifyAdmin(
        string $adminSlug,
        string $type,
        string $title,
        ?string $body = null,
        ?string $actionUrl = null,
        array $meta = [],
        bool $sendEmail = true,
    ): UserNotification {
        return $this->create('admin', $adminSlug, $type, $title, $body, $actionUrl, $meta, $sendEmail);
    }

    /** @return UserNotification[] */
    public function notifyAllAdmins(
        string $type,
        string $title,
        ?string $body = null,
        ?string $actionUrl = null,
        array $meta = [],
        bool $sendEmail = true,
    ): array {
        $notifications = [];
        Admin::query()->where('status', 'active')->each(function (Admin $admin) use (
            &$notifications,
            $type,
            $title,
            $body,
            $actionUrl,
            $meta,
            $sendEmail,
        ) {
            $notifications[] = $this->notifyAdmin(
                $admin->admin_slug,
                $type,
                $title,
                $body,
                $actionUrl,
                $meta,
                $sendEmail,
            );
        });

        return $notifications;
    }

    public function unreadCount(string $recipientType, string $recipientSlug): int
    {
        return UserNotification::query()
            ->where('recipient_type', $recipientType)
            ->where('recipient_slug', $recipientSlug)
            ->whereNull('read_at')
            ->count();
    }

    public function markRead(UserNotification $notification): UserNotification
    {
        if (! $notification->read_at) {
            $notification->update(['read_at' => now()]);
        }

        return $notification->fresh();
    }

    public function markAllRead(string $recipientType, string $recipientSlug): int
    {
        return UserNotification::query()
            ->where('recipient_type', $recipientType)
            ->where('recipient_slug', $recipientSlug)
            ->whereNull('read_at')
            ->update(['read_at' => now()]);
    }

    protected function create(
        string $recipientType,
        string $recipientSlug,
        string $type,
        string $title,
        ?string $body,
        ?string $actionUrl,
        array $meta,
        bool $sendEmail,
    ): UserNotification {
        $notification = UserNotification::create([
            'notification_uuid' => (string) Str::uuid(),
            'recipient_type' => $recipientType,
            'recipient_slug' => $recipientSlug,
            'type' => $type,
            'title' => $title,
            'body' => $body,
            'action_url' => $actionUrl,
            'meta' => $meta ?: null,
        ]);

        if ($sendEmail) {
            SendNotificationEmailJob::dispatch($notification->notification_uuid);
        }

        return $notification;
    }

    public static function clientBaseUrl(): string
    {
        return rtrim((string) (config('custom.urls.client_url') ?: config('custom.urls.frontend_url')), '/');
    }

    public static function adminBaseUrl(): string
    {
        return rtrim((string) (config('custom.urls.admin_url') ?: config('custom.urls.frontend_url')), '/');
    }
}
