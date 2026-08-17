<?php

namespace App\Mail;

use App\Models\Invoice;
use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Queue\SerializesModels;

class InvoiceEmail extends Mailable
{
    use Queueable, SerializesModels;

    public function __construct(
        public Invoice $invoice,
        public array $companySettings,
        public ?string $customMessage = null,
    ) {}

    public function envelope(): Envelope
    {
        return new Envelope(
            subject: 'Invoice ' . $this->invoice->invoice_number . ' from ' . ($this->companySettings['legal_name'] ?? '360 Tours Ghana'),
        );
    }

    public function content(): Content
    {
        return new Content(
            view: 'invoiceMail',
            with: [
                'invoice' => $this->invoice,
                'company' => $this->companySettings,
                'customMessage' => $this->customMessage,
                'subtotal' => $this->subtotal(),
                'total' => $this->total(),
            ],
        );
    }

    public function attachments(): array
    {
        return [];
    }

    protected function subtotal(): float
    {
        return collect($this->invoice->line_items ?? [])->sum(fn (array $item) => ((float) ($item['quantity'] ?? 0)) * ((float) ($item['rate'] ?? 0)));
    }

    protected function total(): float
    {
        $subtotal = $this->subtotal();
        $discount = $subtotal * (((float) $this->invoice->discount_percent) / 100);
        $taxable = $subtotal - $discount + (float) $this->invoice->shipping;
        $tax = $taxable * (((float) $this->invoice->tax_percent) / 100);

        return round($taxable + $tax, 2);
    }
}
