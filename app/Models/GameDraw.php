<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class GameDraw extends Model
{
    use HasFactory;

    public const STATUS_SCHEDULED = 'SCHEDULED';
    public const STATUS_OPEN = 'OPEN';
    public const STATUS_LOCKED = 'LOCKED';
    public const STATUS_RESULT_PENDING = 'RESULT_PENDING';
    public const STATUS_SETTLED = 'SETTLED';
    public const STATUS_VOID = 'VOID';

    protected $fillable = [
        'tenant_id',
        'lotto_game_id',
        'draw_code',
        'status',
        'opens_at',
        'closes_at',
        'winning_numbers',
        'result_entered_by',
        'result_approved_by',
        'settled_at',
    ];

    protected $casts = [
        'opens_at' => 'datetime',
        'closes_at' => 'datetime',
        'settled_at' => 'datetime',
        'winning_numbers' => 'array',
    ];

    public function tenant(): BelongsTo
    {
        return $this->belongsTo(Tenant::class);
    }

    public function lottoGame(): BelongsTo
    {
        return $this->belongsTo(LottoGame::class);
    }

    public function betTickets(): HasMany
    {
        return $this->hasMany(BetTicket::class);
    }

    public function enteredBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'result_entered_by');
    }

    public function approvedBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'result_approved_by');
    }

    public function isOpen(): bool
    {
        return $this->status === self::STATUS_OPEN && now()->lt($this->closes_at);
    }

    public function isLocked(): bool
    {
        return in_array($this->status, [self::STATUS_LOCKED, self::STATUS_RESULT_PENDING, self::STATUS_SETTLED, self::STATUS_VOID]);
    }

    public function isSettled(): bool
    {
        return $this->status === self::STATUS_SETTLED;
    }
}

