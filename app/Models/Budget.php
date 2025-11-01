<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Budget extends Model
{
    use HasFactory;
    protected $guarded = [];

    public function foundation(): BelongsTo
    {
        return $this->belongsTo(Foundation::class);
    }

    // TAMBAHKAN RELASI INI:
    public function department(): BelongsTo
    {
        return $this->belongsTo(Department::class);
    }

    // 'items' adalah nama relasi untuk Repeater di Filament nanti
    public function items(): HasMany
    {
        return $this->hasMany(BudgetItem::class);
    }
}