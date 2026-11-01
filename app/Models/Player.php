<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Player extends Model
{
    use HasFactory;

    protected $fillable = [
        'tenant_id',
        'phone',
        'name',
        'pin_hash',
        'failed_pin_attempts',
        'is_locked',
        'locked_until',
        'last_active_at',
    ];

    protected $casts = [
        'failed_pin_attempts' => 'integer',
        'is_locked' => 'boolean',
        'locked_until' => 'datetime',
        'last_active_at' => 'datetime',
    ];

    public function tenant(): BelongsTo
    {
        return $this->belongsTo(Tenant::class);
    }

    public function betTickets(): HasMany
    {
        return $this->hasMany(BetTicket::class);
    }
}

