<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Otp extends Model
{
    use HasFactory;

    protected $fillable = [
        'token',
        'actor_id',
        'guard',
        'type',
        'channel',
        'expires_at',
    ];

    protected $casts = [
        'expires_at' => 'datetime',
    ];
}
