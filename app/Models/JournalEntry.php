<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class JournalEntry extends Model
{
    use HasFactory;

    // Kita nonaktifkan timestamp (created_at/updated_at)
    // untuk tabel entri agar lebih ringan
    public $timestamps = false;

    protected $fillable = [
        'journal_id',
        'account_id',
        'type',
        'amount',
    ];

    // Relasi ke Induk Jurnal
    public function journal(): BelongsTo
    {
        return $this->belongsTo(Journal::class);
    }

    // Relasi ke Akun (COA)
    public function account(): BelongsTo
    {
        return $this->belongsTo(Account::class);
    }
}