<?php

namespace App\Mail;

use App\Models\Admin;
use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Queue\SerializesModels;

class AdminWelcomeEmail extends Mailable
{
    use Queueable, SerializesModels;

    public function __construct(
        public Admin $admin,
        public string $login_url,
    ) {}

    public function envelope(): Envelope
    {
        return new Envelope(
            subject: 'Welcome to 360 Tours Ghana Admin',
        );
    }

    public function content(): Content
    {
        return new Content(
            view: 'adminWelcomeMail',
        );
    }

    public function attachments(): array
    {
        return [];
    }
}
