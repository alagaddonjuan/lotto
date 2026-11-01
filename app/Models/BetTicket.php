<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class BetTicket extends Model
{
    use HasFactory;

    protected $fillable = [
        'tenant_id',
        'player_id',
        'ticket_reference',
        'operator_ticket_id',
        'game_code',
        'selected_numbers',
        'stake_amount',
        'q4i_share',
        'operator_share',
        'status',
        'payout_amount',
        'idempotency_key',
        'hash',
        'raw_operator_response',
    ];

    protected $casts = [
        'selected_numbers' => 'array',
        'stake_amount' => 'float',
        'q4i_share' => 'float',
        'operator_share' => 'float',
        'payout_amount' => 'float',
        'raw_operator_response' => 'array',
    ];

    public function tenant(): BelongsTo
    {
        return $this->belongsTo(Tenant::class);
    }

    public function player(): BelongsTo
    {
        return $this->belongsTo(Player::class);
    }

    public function ledgerEntries(): HasMany
    {
        return $this->hasMany(LedgerEntry::class);
    }
}

