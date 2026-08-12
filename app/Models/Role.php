<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Role extends Model
{
    use HasFactory;

    protected $fillable = ['name'];

    protected $hidden = [
        'created_at',
        'updated_at',
    ];

    protected $casts = [
        'created_at' => 'datetime',
        'updated_at' => 'datetime',
    ];

    public function permissions(): BelongsToMany
    {
        return $this->belongsToMany(Permission::class, 'role_permissions');
    }

    public function admins(): HasMany
    {
        return $this->hasMany(Admin::class);
    }

    public function toApiArray(): array
    {
        $this->loadMissing('permissions');

        return [
            'id' => $this->id,
            'name' => $this->name,
            'permissions' => $this->permissions->map(fn (Permission $permission) => $permission->toApiArray())->values()->all(),
        ];
    }
}
