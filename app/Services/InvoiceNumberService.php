<?php

namespace App\Services;

use App\Models\Invoice;
use Illuminate\Support\Str;

class InvoiceNumberService
{
    public function generate(): string
    {
        $datePrefix = now()->format('Ymd');
        $prefix = "INV-{$datePrefix}-";

        $latest = Invoice::query()
            ->withTrashed()
            ->where('invoice_number', 'like', $prefix . '%')
            ->orderByDesc('invoice_number')
            ->value('invoice_number');

        $sequence = 1;

        if ($latest && preg_match('/-(\d{4})$/', $latest, $matches)) {
            $sequence = ((int) $matches[1]) + 1;
        }

        $number = $prefix . Str::padLeft((string) $sequence, 4, '0');

        while (Invoice::query()->withTrashed()->where('invoice_number', $number)->exists()) {
            $sequence++;
            $number = $prefix . Str::padLeft((string) $sequence, 4, '0');
        }

        return $number;
    }
}
