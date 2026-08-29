<?php

namespace App\Http\Controllers;

use App\Models\UserNotification;
use App\Services\NotificationService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

abstract class NotificationControllerBase extends Controller
{
    abstract protected function recipientType(): string;

    abstract protected function recipientSlug(Request $request): string;

    public function __construct(protected NotificationService $notifications) {}

    public function index(Request $request): JsonResponse
    {
        $query = UserNotification::query()
            ->where('recipient_type', $this->recipientType())
            ->where('recipient_slug', $this->recipientSlug($request))
            ->latest();

        if ($request->boolean('unread')) {
            $query->whereNull('read_at');
        }

        $paginator = self::paginateQuery($request, $query, 20);

        return self::paginatedApiResponse('Notifications retrieved', $paginator, fn (UserNotification $n) => $n->toNotificationArray());
    }

    public function unreadCount(Request $request): JsonResponse
    {
        $count = $this->notifications->unreadCount(
            $this->recipientType(),
            $this->recipientSlug($request),
        );

        return self::apiResponse(false, 'Action Successful', (string) self::API_SUCCESS, 'Unread count retrieved', [
            'count' => $count,
        ]);
    }

    public function markRead(Request $request, UserNotification $notification): JsonResponse
    {
        if ($notification->recipient_type !== $this->recipientType()
            || $notification->recipient_slug !== $this->recipientSlug($request)) {
            return self::apiResponse(true, 'Action Unsuccessful', (string) self::API_NOT_FOUND, 'Notification not found', []);
        }

        return self::apiResponse(false, 'Action Successful', (string) self::API_SUCCESS, 'Notification marked as read', [
            'notification' => $this->notifications->markRead($notification)->toNotificationArray(),
        ]);
    }

    public function markAllRead(Request $request): JsonResponse
    {
        $updated = $this->notifications->markAllRead(
            $this->recipientType(),
            $this->recipientSlug($request),
        );

        return self::apiResponse(false, 'Action Successful', (string) self::API_SUCCESS, 'All notifications marked as read', [
            'updated' => $updated,
        ]);
    }
}
