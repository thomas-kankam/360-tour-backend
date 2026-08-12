<?php

namespace Database\Seeders;

use App\Models\Admin;
use Illuminate\Database\Seeder;
use Illuminate\Support\Str;

class AdminSeeder extends Seeder
{
    public function run(): void
    {
        Admin::query()->updateOrCreate(
            ['email' => "kankamthomas6@gmail.com"],
            [
                'admin_slug' => (string) Str::uuid(),
                'first_name' => 'Thomas',
                'last_name' => 'Kankam',
                'phone_number' => '233556906969',
                'role_id' => 1,
                'status' => 'active',
            ]
        );
    }
}
