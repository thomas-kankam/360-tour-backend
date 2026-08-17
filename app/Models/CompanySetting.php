<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class CompanySetting extends Model
{
    protected $fillable = [
        'settings',
    ];

    protected $casts = [
        'settings' => 'array',
        'created_at' => 'datetime',
        'updated_at' => 'datetime',
    ];

    public static function defaultSettings(): array
    {
        return [
            'legal_name' => '360 Tours and Investment Limited',
            'tagline' => '',
            'email' => 'info@360toursghana.com',
            'phone' => '',
            'website' => 'https://360toursghana.com',
            'address_line_1' => '',
            'address_line_2' => '',
            'tax_id' => '',
            'invoice_logo' => '',
            'bank_name' => '',
            'bank_account' => '',
            'bank_routing' => '',
            'payment_notes' => '',
            'paypal_or_mobile_money' => '',
            'invoice_terms' => 'Payment due within 14 days.',
            'default_currency' => 'USD',
            'default_tax_percent' => 0,
        ];
    }

    public static function current(): self
    {
        $record = static::query()->first();

        if ($record) {
            return $record;
        }

        return static::query()->create([
            'settings' => static::defaultSettings(),
        ]);
    }

    public function mergedSettings(): array
    {
        return array_merge(static::defaultSettings(), $this->settings ?? []);
    }
}
