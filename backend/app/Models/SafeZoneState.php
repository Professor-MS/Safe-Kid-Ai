<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class SafeZoneState extends Model
{
    use HasFactory;

    protected $fillable = [
        'child_id',
        'safe_zone_id',
        'is_inside',
        'last_evaluated_at',
    ];

    protected $casts = [
        'is_inside' => 'boolean',
        'last_evaluated_at' => 'datetime',
    ];

    public function child(): BelongsTo
    {
        return $this->belongsTo(User::class, 'child_id');
    }

    public function safeZone(): BelongsTo
    {
        return $this->belongsTo(SafeZone::class);
    }
}

