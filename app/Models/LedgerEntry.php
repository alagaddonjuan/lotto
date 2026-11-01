<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class LedgerEntry extends Model
{
    use HasFactory;

    public $timestamps = false; // We use created_at only (immutable)

    protected $fillable = [
        'tenant_id',
        'bet_ticket_id',
        'entry_type',
        'debit_account',
        'credit_account',
        'amount',
        'currency',
        'previous_hash',
        'hash',
        'metadata',
        'created_at',
    ];

    protected $casts = [
        'amount' => 'float',
        'metadata' => 'array',
        'created_at' => 'datetime',
    ];

    public function tenant(): BelongsTo
    {
        return $this->belongsTo(Tenant::class);
    }

    public function betTicket(): BelongsTo
    {
        return $this->belongsTo(BetTicket::class);
    }
}

