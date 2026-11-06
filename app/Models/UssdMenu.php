<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class UssdMenu extends Model
{
    use HasFactory;

    protected $fillable = [
        'id',
        'tenant_id',
        'menu_name',
        'service_code',
        'is_active',
    ];

    public function tenant(): BelongsTo
    {
        return $this->belongsTo(Tenant::class);
    }

    public function items(): HasMany
    {
        return $this->hasMany(UssdMenuItem::class);
    }

    public function rootItem(): ?UssdMenuItem
    {
        return $this->items()->whereNull('parent_item_id')->where('option_trigger', 'ROOT')->first();
    }
}

