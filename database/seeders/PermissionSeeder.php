<?php

namespace Database\Seeders;

use App\Models\Permission;
use App\Models\Role;
use Illuminate\Database\Seeder;

class PermissionSeeder extends Seeder
{
    public function run(): void
    {
        $permissions = [
            ['name' => 'user_management', 'label' => 'User Management'],
            ['name' => 'listing_management', 'label' => 'Listing Management'],
            ['name' => 'booking_management', 'label' => 'Booking Management'],
            ['name' => 'client_management', 'label' => 'Client Management'],
            ['name' => 'contact_management', 'label' => 'Contact Management'],
            ['name' => 'role_management', 'label' => 'Role Management'],
        ];

        foreach ($permissions as $permission) {
            Permission::query()->updateOrCreate(
                ['name' => $permission['name']],
                ['label' => $permission['label']]
            );
        }

        $role = Role::query()->updateOrCreate(
            ['name' => 'Super Admin']
        );

        $role->permissions()->sync(
            Permission::query()->pluck('id')->all()
        );
    }
}
