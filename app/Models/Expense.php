<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\MorphOne; // <-- Penting

class Expense extends Model
{
    use HasFactory;

    protected $fillable = [
        'foundation_id',
        'school_id',
        'expense_account_id', // Akun Beban
        'cash_account_id',    // Akun Kas/Bank
        'amount',
        'date',
        'description',
        'proof_file',
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

    // Relasi ke Akun Beban
    public function expenseAccount(): BelongsTo
    {
        return $this->belongsTo(Account::class, 'expense_account_id');
    }

    // Relasi ke Akun Kas/Bank
    public function cashAccount(): BelongsTo
    {
        return $this->belongsTo(Account::class, 'cash_account_id');
    }

    // Relasi ke Jurnal (Polimorfik)
    // 1 Pengeluaran = 1 Jurnal
    public function journal(): MorphOne
    {
        return $this->morphOne(Journal::class, 'referenceable');
    }
}