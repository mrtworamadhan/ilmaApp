<?php

namespace App\Models\Payroll;

use App\Models\Foundation;
use App\Models\School;
use App\Models\Teacher;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\MorphTo;

class EmployeePayroll extends Model
{
    use HasFactory;

    protected $fillable = [
        'foundation_id',
        'school_id',
        'payroll_component_id',
        'teacher_id',
        'amount',
    ];

    protected $casts = [
        'amount' => 'decimal:2',
    ];

    public function foundation(): BelongsTo
    {
        return $this->belongsTo(Foundation::class);
    }

    public function school(): BelongsTo
    {
        return $this->belongsTo(School::class);
    }

    /**
     * Komponen gaji apa ini.
     */
    public function payrollComponent(): BelongsTo
    {
        return $this->belongsTo(PayrollComponent::class);
    }

    /**
     * Siapa pemilik komponen gaji ini (bisa Guru atau User).
     */
    public function teacher(): BelongsTo // <-- Ganti dari employable()
    {
        return $this->belongsTo(Teacher::class);
    }
}