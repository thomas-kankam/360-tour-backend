<?php

namespace App\Jobs;

use App\Mail\InvoiceEmail;
use App\Models\Client;
use App\Models\CompanySetting;
use App\Models\Invoice;
use App\Models\UserNotification;
use App\Services\NotificationService;
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

    public function handle(NotificationService $notifications): void
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

        $clientSlug = $invoice->client_slug;

        if (! $clientSlug) {
            $clientSlug = Client::query()->where('email', $this->email)->value('client_slug');
            if ($clientSlug) {
                $invoice->update(['client_slug' => $clientSlug]);
            }
        }

        if ($clientSlug) {
            $clientUrl = NotificationService::clientBaseUrl();

            $notifications->notifyClient(
                clientSlug: $clientSlug,
                type: UserNotification::TYPE_INVOICE_SENT,
                title: 'Invoice ' . $invoice->invoice_number,
                body: $this->message ?: 'You have received a new invoice from 360 Tours Ghana.',
                actionUrl: $clientUrl . '/my-invoices/' . $invoice->invoice_uuid,
                meta: ['invoice_uuid' => $invoice->invoice_uuid],
                sendEmail: false,
            );
        }
    }
}
