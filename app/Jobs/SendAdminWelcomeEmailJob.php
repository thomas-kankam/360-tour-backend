<?php

namespace App\Jobs;

use App\Mail\AdminWelcomeEmail;
use App\Models\Admin;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Mail;

class SendAdminWelcomeEmailJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public function __construct(
        public Admin $admin,
    ) {}

    public function handle(): void
    {
        $this->admin->loadMissing('role');

        Mail::to($this->admin->email)->send(new AdminWelcomeEmail(
            admin: $this->admin,
            login_url: config('custom.urls.admin_url'),
        ));
    }
}
