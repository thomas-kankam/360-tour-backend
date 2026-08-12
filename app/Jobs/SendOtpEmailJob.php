<?php

namespace App\Jobs;

use App\Mail\OtpEmail;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Mail;

class SendOtpEmailJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public function __construct(
        public string $email,
        public int $otp,
        public string $purpose = 'login',
    ) {}

    public function handle(): void
    {
        $loginUrl = match ($this->purpose) {
            'registration' => config('custom.urls.frontend_url'),
            default => config('custom.urls.frontend_url'),
        };

        Mail::to($this->email)->send(new OtpEmail(
            email: $this->email,
            otp: $this->otp,
            purpose: $this->purpose,
            login_url: $loginUrl,
        ));
    }
}
