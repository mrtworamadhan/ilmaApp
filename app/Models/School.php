<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class School extends Model
{
    use HasFactory;

    protected $fillable = [
        'foundation_id',
        'name',
        'level',
        'address',
        'phone',
        'headmaster',
    ];

    /**
     * Definisi relasi: 1 Sekolah milik 1 Yayasan
     * Sesuai ERD 
     */
    public function foundation(): BelongsTo
    {
        return $this->belongsTo(Foundation::class);
    }
}