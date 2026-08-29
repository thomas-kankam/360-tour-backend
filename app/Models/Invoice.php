<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\SoftDeletes;

class Invoice extends Model
{
    use HasFactory, SoftDeletes;

    protected $fillable = [
        'invoice_uuid',
        'invoice_number',
        'status',
        'issue_date',
        'due_date',
        'reference',
        'project',
        'currency',
        'tax_percent',
        'discount_percent',
        'shipping',
        'notes',
        'terms',
        'payment_details',
        'billed_to_name',
        'billed_to_email',
        'billed_to_phone',
        'billed_to_address',
        'client_slug',
        'sent_at',
        'last_sent_to_email',
        'line_items',
    ];

    protected $casts = [
        'issue_date' => 'date',
        'due_date' => 'date',
        'tax_percent' => 'decimal:2',
        'discount_percent' => 'decimal:2',
        'shipping' => 'decimal:2',
        'line_items' => 'array',
        'sent_at' => 'datetime',
        'deleted_at' => 'datetime',
        'created_at' => 'datetime',
        'updated_at' => 'datetime',
    ];

    public function getRouteKeyName(): string
    {
        return 'invoice_uuid';
    }

    public function client(): BelongsTo
    {
        return $this->belongsTo(Client::class, 'client_slug', 'client_slug');
    }

    public function toInvoiceArray(): array
    {
        return [
            'id' => $this->invoice_uuid,
            'invoice_number' => $this->invoice_number,
            'status' => $this->status,
            'issue_date' => $this->issue_date?->format('Y-m-d'),
            'due_date' => $this->due_date?->format('Y-m-d'),
            'reference' => $this->reference ?? '',
            'project' => $this->project ?? '',
            'currency' => $this->currency,
            'tax_percent' => (float) $this->tax_percent,
            'discount_percent' => (float) $this->discount_percent,
            'shipping' => (float) $this->shipping,
            'notes' => $this->notes ?? '',
            'terms' => $this->terms ?? '',
            'payment_details' => $this->payment_details ?? '',
            'billed_to_name' => $this->billed_to_name,
            'billed_to_email' => $this->billed_to_email,
            'billed_to_phone' => $this->billed_to_phone ?? '',
            'billed_to_address' => $this->billed_to_address ?? '',
            'client_slug' => $this->client_slug,
            'line_items' => $this->line_items ?? [],
            'sent_at' => $this->sent_at?->toIso8601String(),
            'last_sent_to_email' => $this->last_sent_to_email,
            'created_at' => $this->created_at?->toIso8601String(),
            'updated_at' => $this->updated_at?->toIso8601String(),
        ];
    }
}
