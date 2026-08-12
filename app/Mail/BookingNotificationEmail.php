<?php

namespace App\Mail;

use App\Models\Booking;
use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Queue\SerializesModels;

class BookingNotificationEmail extends Mailable
{
    use Queueable, SerializesModels;

    public function __construct(
        public string $recipientName,
        public string $headline,
        public string $body,
        public Booking $booking,
        public ?string $actionUrl = null,
    ) {}

    public function envelope(): Envelope
    {
        return new Envelope(
            subject: $this->headline,
        );
    }

    public function content(): Content
    {
        return new Content(
            view: 'bookingNotificationMail',
        );
    }

    public function attachments(): array
    {
        return [];
    }
}
