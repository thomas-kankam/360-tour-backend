<?php
namespace App\Models;

use App\Traits\Helpers;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Laravel\Passport\HasApiTokens;

class Actor extends Authenticatable
{
    use HasFactory, SoftDeletes, HasApiTokens, Notifiable, Helpers;

    protected $hidden = [
        'remember_token',
        'deleted_at',
    ];

    protected $casts = [
        'deleted_at' => 'datetime',
        'created_at' => 'datetime',
        'updated_at' => 'datetime',
    ];
}
