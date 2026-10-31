<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;

class User extends Authenticatable
{
    use HasFactory, Notifiable;

    protected $fillable = [
        'firebase_uid',
        'name',
        'email',
        'role',
        'battery_level',
        'last_seen_at',
    ];

    protected $casts = [
        'battery_level' => 'integer',
        'last_seen_at' => 'datetime',
    ];

    public function isParent(): bool
    {
        return $this->role === 'parent';
    }

    public function isChild(): bool
    {
        return $this->role === 'child';
    }

    public function deviceTokens(): HasMany
    {
        return $this->hasMany(DeviceToken::class);
    }

    public function pairingCodes(): HasMany
    {
        return $this->hasMany(PairingCode::class, 'parent_id');
    }

    public function children(): BelongsToMany
    {
        return $this->belongsToMany(User::class, 'parent_child_links', 'parent_id', 'child_id')
            ->withPivot(['status', 'linked_at', 'revoked_by', 'revoked_at'])
            ->withTimestamps();
    }

    public function parents(): BelongsToMany
    {
        return $this->belongsToMany(User::class, 'parent_child_links', 'child_id', 'parent_id')
            ->withPivot(['status', 'linked_at', 'revoked_by', 'revoked_at'])
            ->withTimestamps();
    }

    public function locationHistories(): HasMany
    {
        return $this->hasMany(LocationHistory::class, 'child_id');
    }

    public function safeZones(): HasMany
    {
        return $this->hasMany(SafeZone::class, 'parent_id');
    }

    public function sosAlerts(): HasMany
    {
        return $this->hasMany(SosAlert::class, 'child_id');
    }
}
