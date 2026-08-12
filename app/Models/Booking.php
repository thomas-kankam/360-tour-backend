<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;

class Booking extends Model
{
    use HasFactory, SoftDeletes;

    protected $fillable = [
        'booking_code',
        'client_slug',
        'booked_by_type',
        'booked_by_slug',
        'tour_slug',
        'booking_type',
        'selected_date',
        'travelers',
        'payment_mode',
        'payment_status',
        'amount',
        'currency',
        'lead_traveler',
        'group_details',
        'special_requests',
        'dietary_needs',
        'additional_travelers',
        'status',
        'admin_slug',
        'created_by_admin_slug',
    ];

    protected $casts = [
        'selected_date' => 'date',
        'lead_traveler' => 'array',
        'group_details' => 'array',
        'additional_travelers' => 'array',
        'amount' => 'decimal:2',
    ];

    public function getRouteKeyName(): string
    {
        return 'booking_code';
    }

    public function tour(): BelongsTo
    {
        return $this->belongsTo(Tour::class, 'tour_slug', 'tour_slug');
    }

    public function client(): BelongsTo
    {
        return $this->belongsTo(Client::class, 'client_slug', 'client_slug');
    }

    public function admin(): BelongsTo
    {
        return $this->belongsTo(Admin::class, 'admin_slug', 'admin_slug');
    }

    public function payments(): HasMany
    {
        return $this->hasMany(Payment::class, 'booking_code', 'booking_code');
    }

    public function toBookingArray(?string $paymentUrl = null): array
    {
        $data = [
            'bookingCode' => $this->booking_code,
            'clientSlug' => $this->client_slug,
            'bookedByType' => $this->booked_by_type,
            'bookedBySlug' => $this->booked_by_slug,
            'bookingType' => $this->booking_type,
            'tourSlug' => $this->tour_slug,
            'selectedDate' => $this->selected_date?->format('Y-m-d'),
            'travelers' => $this->travelers,
            'paymentMode' => $this->payment_mode,
            'paymentStatus' => $this->payment_status,
            'amount' => (float) $this->amount,
            'currency' => $this->currency,
            'leadTraveler' => $this->lead_traveler,
            'groupDetails' => $this->group_details,
            'specialRequests' => $this->special_requests,
            'dietaryNeeds' => $this->dietary_needs,
            'additionalTravelers' => $this->additional_travelers ?? [],
            'status' => $this->status,
            'adminSlug' => $this->admin_slug,
            'createdAt' => $this->created_at,
            'updatedAt' => $this->updated_at,
        ];

        if ($paymentUrl) {
            $data['paymentUrl'] = $paymentUrl;
        }

        if ($this->relationLoaded('tour') && $this->tour) {
            $data['tour'] = $this->tour->toListingArray();
        }

        if ($this->relationLoaded('client') && $this->client) {
            $data['client'] = $this->client->toArray();
        }

        if ($this->relationLoaded('admin') && $this->admin) {
            $data['admin'] = $this->admin->toAdminArray();
        }

        return $data;
    }
}
