<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class UserNotification extends Model
{
    public const TYPE_INVOICE_SENT = 'invoice_sent';

    public const TYPE_INVOICE_REQUEST = 'invoice_request';

    public const TYPE_QUOTE_REQUEST = 'quote_request';

    public const TYPE_INVOICE_REQUEST_RESPONSE = 'invoice_request_response';

    public const TYPE_QUOTE_REQUEST_RESPONSE = 'quote_request_response';

    public const TYPE_RATING_PENDING = 'rating_pending';

    public const TYPE_RATING_APPROVED = 'rating_approved';

    public const TYPE_RATING_REJECTED = 'rating_rejected';

    public const TYPE_CLIENT_REGISTERED = 'client_registered';

    public const TYPE_BOOKING_CREATED = 'booking_created';

    public const TYPE_BOOKING_UPDATED = 'booking_updated';

    public const TYPE_PAYMENT_SUCCESS = 'payment_success';

    public const TYPE_PAYMENT_FAILED = 'payment_failed';

    protected $table = 'user_notifications';

    protected $primaryKey = 'notification_uuid';

    protected $keyType = 'string';

    public $incrementing = false;

    protected $fillable = [
        'notification_uuid',
        'recipient_type',
        'recipient_slug',
        'type',
        'title',
        'body',
        'action_url',
        'meta',
        'read_at',
    ];

    protected $casts = [
        'meta' => 'array',
        'read_at' => 'datetime',
        'created_at' => 'datetime',
        'updated_at' => 'datetime',
    ];

    public function getRouteKeyName(): string
    {
        return 'notification_uuid';
    }

    public function isRead(): bool
    {
        return $this->read_at !== null;
    }

    public function toNotificationArray(): array
    {
        return [
            'id' => $this->notification_uuid,
            'type' => $this->type,
            'title' => $this->title,
            'body' => $this->body,
            'action_url' => $this->action_url,
            'meta' => $this->meta ?? [],
            'read_at' => $this->read_at?->toIso8601String(),
            'is_read' => $this->isRead(),
            'created_at' => $this->created_at?->toIso8601String(),
        ];
    }
}
