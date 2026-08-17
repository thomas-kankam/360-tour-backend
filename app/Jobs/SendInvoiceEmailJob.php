<?php

namespace App\Jobs;

use App\Mail\InvoiceEmail;
use App\Models\CompanySetting;
use App\Models\Invoice;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Mail;

class SendInvoiceEmailJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public function __construct(
        public string $invoiceUuid,
        public string $email,
        public bool $attachPdf = false,
        public ?string $message = null,
    ) {}

    public function handle(): void
    {
        $invoice = Invoice::query()->where('invoice_uuid', $this->invoiceUuid)->first();

        if (! $invoice) {
            return;
        }

        $companySettings = CompanySetting::current()->mergedSettings();

        Mail::to($this->email)->send(new InvoiceEmail(
            invoice: $invoice,
            companySettings: $companySettings,
            customMessage: $this->message,
        ));
    }
}
