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
        'withdrawal_pin_hash',
        'cash_balance_kobo',
        'bonus_balance_kobo',
        'failed_pin_attempts',
        'is_locked',
        'locked_until',
        'last_active_at',
    ];

    protected $casts = [
        'cash_balance_kobo' => 'integer',
        'bonus_balance_kobo' => 'integer',
        'failed_pin_attempts' => 'integer',
        'is_locked' => 'boolean',
        'locked_until' => 'datetime',
        'last_active_at' => 'datetime',
    ];

    public function totalBalanceKobo(): int
    {
        return (int) ($this->cash_balance_kobo + $this->bonus_balance_kobo);
    }

    public function hasSufficientCash(int $kobo): bool
    {
        return $this->cash_balance_kobo >= $kobo;
    }

    public function hasSufficientBonus(int $kobo): bool
    {
        return $this->bonus_balance_kobo >= $kobo;
    }

    public function tenant(): BelongsTo
    {
        return $this->belongsTo(Tenant::class);
    }

    public function betTickets(): HasMany
    {
        return $this->hasMany(BetTicket::class);
    }
}

