<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class LottoGame extends Model
{
    use HasFactory;

    protected $fillable = [
        'tenant_id',
        'game_code',
        'name',
        'description',
        'min_stake',
        'max_stake',
        'total_pool_numbers',
        'required_picks',
        'is_active',
    ];

    protected $casts = [
        'min_stake' => 'float',
        'max_stake' => 'float',
        'total_pool_numbers' => 'integer',
        'required_picks' => 'integer',
        'is_active' => 'boolean',
    ];

    public function tenant(): BelongsTo
    {
        return $this->belongsTo(Tenant::class);
    }
}

