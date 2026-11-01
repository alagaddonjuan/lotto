<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Tenant extends Model
{
    use HasFactory;

    protected $fillable = [
        'name',
        'slug',
        'country_code',
        'currency',
        'whatsapp_number',
        'whatsapp_driver',
        'driver_config',
        'operator_driver',
        'operator_api_url',
        'operator_api_key',
        'q4i_split_percent',
        'operator_split_percent',
        'is_active',
    ];

    protected $casts = [
        'driver_config' => 'array',
        'q4i_split_percent' => 'float',
        'operator_split_percent' => 'float',
        'is_active' => 'boolean',
    ];

    public function players(): HasMany
    {
        return $this->hasMany(Player::class);
    }

    public function lottoGames(): HasMany
    {
        return $this->hasMany(LottoGame::class);
    }

    public function betTickets(): HasMany
    {
        return $this->hasMany(BetTicket::class);
    }

    public function ledgerEntries(): HasMany
    {
        return $this->hasMany(LedgerEntry::class);
    }
}

