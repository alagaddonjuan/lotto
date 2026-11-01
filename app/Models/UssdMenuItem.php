<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class UssdMenuItem extends Model
{
    use HasFactory;

    protected $fillable = [
        'id',
        'ussd_menu_id',
        'parent_item_id',
        'option_trigger',
        'response_type',
        'response_text',
    ];

    public function menu(): BelongsTo
    {
        return $this->belongsTo(UssdMenu::class, 'ussd_menu_id');
    }

    public function parent(): BelongsTo
    {
        return $this->belongsTo(UssdMenuItem::class, 'parent_item_id');
    }

    public function children(): HasMany
    {
        return $this->hasMany(UssdMenuItem::class, 'parent_item_id');
    }
}

