<?php

namespace App\Models;

class Client extends Actor
{
    protected $fillable = [
        'client_slug',
        'first_name',
        'last_name',
        'phone_number',
        'email',
        'is_verified',
        'verified_at',
        'location',
        'status',
        'profile_image',
    ];

    protected $hidden = [
        "deleted_at",
    ];

    protected $casts = [
        'deleted_at' => 'datetime',
        'created_at' => 'datetime',
        'updated_at' => 'datetime',
        'verified_at' => 'datetime',
        'is_verified' => 'boolean',
        'profile_image' => 'string',
        'status' => 'string',
    ];

    public function getRouteKeyName(): string
    {
        return "client_slug";
    }
}
