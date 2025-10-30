<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class FeeCategory extends Model
{
    use HasFactory;

    protected $fillable = [
        'foundation_id',
        'name',
        'account_id',
    ];

    // Relasi ke Yayasan (Tenant)
    public function foundation(): BelongsTo
    {
        return $this->belongsTo(Foundation::class);
    }

    // Relasi ke Akun (COA)
    public function account(): BelongsTo
    {
        return $this->belongsTo(Account::class);
    }
}