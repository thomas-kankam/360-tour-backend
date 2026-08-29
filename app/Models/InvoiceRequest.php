<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class InvoiceRequest extends Model
{
    protected $primaryKey = 'request_uuid';

    protected $keyType = 'string';

    public $incrementing = false;

    protected $fillable = [
        'request_uuid',
        'client_slug',
        'type',
        'message',
        'status',
        'admin_response',
        'invoice_uuid',
        'admin_slug',
    ];

    protected $casts = [
        'created_at' => 'datetime',
        'updated_at' => 'datetime',
    ];

    public function getRouteKeyName(): string
    {
        return 'request_uuid';
    }

    public function client(): BelongsTo
    {
        return $this->belongsTo(Client::class, 'client_slug', 'client_slug');
    }

    public function invoice(): BelongsTo
    {
        return $this->belongsTo(Invoice::class, 'invoice_uuid', 'invoice_uuid');
    }

    public function toRequestArray(bool $includeClient = false): array
    {
        $data = [
            'id' => $this->request_uuid,
            'type' => $this->type,
            'message' => $this->message,
            'status' => $this->status,
            'admin_response' => $this->admin_response,
            'invoice_uuid' => $this->invoice_uuid,
            'client_slug' => $this->client_slug,
            'created_at' => $this->created_at?->toIso8601String(),
            'updated_at' => $this->updated_at?->toIso8601String(),
        ];

        if ($includeClient && $this->relationLoaded('client') && $this->client) {
            $data['client_name'] = trim($this->client->first_name . ' ' . ($this->client->last_name ?? ''));
            $data['client_email'] = $this->client->email;
        }

        return $data;
    }
}
