<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Account extends Model
{
    use HasFactory;

    protected $fillable = [
        'foundation_id',
        'code',
        'name',
        'type',
        'category',
        'parent_id',
    ];

    // Relasi ke Yayasan (Tenant)
    public function foundation(): BelongsTo
    {
        return $this->belongsTo(Foundation::class);
    }

    // Relasi ke induknya sendiri (untuk hierarki)
    public function parent(): BelongsTo
    {
        return $this->belongsTo(Account::class, 'parent_id');
    }
    public function journalEntries(): HasMany
    {
        return $this->hasMany(JournalEntry::class);
    }
}