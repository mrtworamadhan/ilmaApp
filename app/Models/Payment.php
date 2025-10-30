<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Payment extends Model
{
    use HasFactory;

    protected $fillable = [
        'foundation_id',
        'school_id',
        'student_id',
        'bill_id',
        'payment_for',
        'payment_method',
        'amount_paid',
        'paid_at',
        'status',
        'xendit_invoice_id',
        'description',
        'created_by',
    ];

    /**
     * Get the attributes that should be cast.
     *
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'paid_at' => 'datetime',
        ];
    }

    // Relasi ke Yayasan (Tenant)
    public function foundation(): BelongsTo
    {
        return $this->belongsTo(Foundation::class);
    }

    // Relasi ke Sekolah
    public function school(): BelongsTo
    {
        return $this->belongsTo(School::class);
    }

    // Relasi ke Siswa
    public function student(): BelongsTo
    {
        return $this->belongsTo(Student::class);
    }

    // Relasi ke Tagihan (bisa null)
    public function bill(): BelongsTo
    {
        return $this->belongsTo(Bill::class);
    }

    // Relasi ke User (Bendahara yg input)
    public function creator(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }
}