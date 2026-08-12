<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Payment extends Model
{
    use HasFactory;

    protected $fillable = [
        'payment_slug',
        'booking_code',
        'paystack_reference',
        'paystack_access_code',
        'amount',
        'currency',
        'status',
        'payment_url',
        'paystack_response',
        'paid_at',
    ];

    protected $casts = [
        'amount' => 'decimal:2',
        'paystack_response' => 'array',
        'paid_at' => 'datetime',
    ];

    public function getRouteKeyName(): string
    {
        return 'payment_slug';
    }

    public function booking(): BelongsTo
    {
        return $this->belongsTo(Booking::class, 'booking_code', 'booking_code');
    }

    public function toPaymentArray(): array
    {
        $data = [
            'paymentSlug' => $this->payment_slug,
            'bookingCode' => $this->booking_code,
            'paymentMethod' => $this->paymentMethod(),
            'reference' => $this->paystack_reference,
            'amount' => (float) $this->amount,
            'currency' => $this->currency,
            'status' => $this->apiStatus(),
            'paymentUrl' => $this->payment_url,
            'paidAt' => $this->paid_at,
            'createdAt' => $this->created_at,
            'updatedAt' => $this->updated_at,
        ];

        if ($this->relationLoaded('booking') && $this->booking) {
            $data['booking'] = $this->booking->toBookingArray();
        }

        return $data;
    }

    public function apiStatus(): string
    {
        return $this->status === 'success' ? 'paid' : $this->status;
    }

    public function paymentMethod(): string
    {
        return $this->paystack_reference ? 'online' : 'onsite';
    }

    public static function statusFilterValue(string $status): ?string
    {
        return match (strtolower($status)) {
            'paid' => 'success',
            'failed' => 'failed',
            'pending' => 'pending',
            default => null,
        };
    }
}
