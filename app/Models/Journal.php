<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\MorphTo;

class Journal extends Model
{
    use HasFactory;

    protected $fillable = [
        'foundation_id',
        'school_id',
        'date',
        'description',
        'referenceable_id',
        'referenceable_type',
        'created_by',
    ];

    protected $casts = [
        'date' => 'date',
    ];

    // Relasi ke Yayasan
    public function foundation(): BelongsTo
    {
        return $this->belongsTo(Foundation::class);
    }

    // Relasi ke Sekolah (bisa null)
    public function school(): BelongsTo
    {
        return $this->belongsTo(School::class);
    }

    // Relasi ke User (pembuat)
    public function creator(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    // Relasi ke referensi (Payment / Expense)
    public function referenceable(): MorphTo
    {
        return $this->morphTo();
    }

    public function entries(): HasMany
    {
        return $this->hasMany(JournalEntry::class);
    }

    public function debitEntries(): HasMany
    {
        return $this->hasMany(JournalEntry::class)->where('type', 'debit');
    }
    
    public function creditEntries(): HasMany
    {
        return $this->hasMany(JournalEntry::class)->where('type', 'credit');
    }
}