<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Admin extends Actor
{
    protected $fillable = [
        'admin_slug',
        'role_id',
        'first_name',
        'last_name',
        'phone_number',
        'email',
        'profile_image',
        'status',
    ];

    protected $hidden = [
        "deleted_at",
        "role_id",
    ];

    protected $casts = [
        'deleted_at'        => 'datetime',
        'created_at'     => 'datetime',
        'updated_at'     => 'datetime',
        "profile_image"     => "string",
        "status"            => "string",
    ];

    public function role(): BelongsTo
    {
        return $this->belongsTo(Role::class, 'role_id', 'id');
    }

    public function hasPermission(string $permission): bool
    {
        if (! $this->role_id) {
            return false;
        }

        $this->loadMissing('role.permissions');

        return $this->role?->permissions->contains('name', $permission) ?? false;
    }

    public function toAdminArray(): array
    {
        static::persistActorProfileImage($this);

        return [
            'adminSlug' => $this->admin_slug,
            'firstName' => $this->first_name,
            'lastName' => $this->last_name,
            'email' => $this->email,
            'phoneNumber' => $this->phone_number,
            'status' => $this->status,
            'profileImage' => static::normalizePublicUrl($this->profile_image),
            'createdAt' => $this->created_at,
            'updatedAt' => $this->updated_at,
        ];
    }

    public function toAuthArray(): array
    {
        static::persistActorProfileImage($this);

        $data = $this->toArray();
        $data['profile_image'] = static::normalizePublicUrl($this->profile_image);
        $data['profileImage'] = $data['profile_image'];
        $this->loadMissing('role.permissions');

        if ($this->role) {
            $data['role'] = [
                'id' => $this->role->id,
                'name' => $this->role->name,
                'permissions' => $this->role->permissions->map(fn($p) => [
                    'id' => $p->id,
                    'name' => $p->name,
                    'label' => $p->label,
                ])->values()->all(),
            ];
        }

        return $data;
    }
}
