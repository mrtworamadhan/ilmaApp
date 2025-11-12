<?php

namespace App\Models\Payroll;

use App\Models\Foundation;
use App\Models\School;
use App\Models\Teacher;
use App\Models\Expense;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Payslip extends Model
{
    use HasFactory;

    protected $fillable = [
        'foundation_id',
        'school_id',
        'teacher_id',
        'month',
        'year',
        'total_allowance',
        'total_deduction',
        'net_pay',
        'status',
        'expense_id',
    ];

    protected $casts = [
        'total_allowance' => 'decimal:2',
        'total_deduction' => 'decimal:2',
        'net_pay' => 'decimal:2',
    ];

    public function foundation(): BelongsTo
    {
        return $this->belongsTo(Foundation::class);
    }

    public function school(): BelongsTo
    {
        return $this->belongsTo(School::class);
    }

    public function teacher(): BelongsTo
    {
        return $this->belongsTo(Teacher::class);
    }

    /**
     * Relasi ke pengeluaran (expense) yang terkait
     */
    public function expense(): BelongsTo
    {
        return $this->belongsTo(Expense::class);
    }

    /**
     * Rincian (detail) dari slip gaji ini
     */
    public function details(): HasMany
    {
        return $this->hasMany(PayslipDetail::class);
    }
}