<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;

class FeeStructure extends Model
{
    use HasFactory;

    protected $fillable = [
        'foundation_id',
        'school_id',
        'fee_category_id',
        'name',
        'amount',
        'billing_cycle',
        'is_active',
        'grade_level',
    ];

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

    // Relasi ke Kategori Biaya
    public function feeCategory(): BelongsTo
    {
        return $this->belongsTo(FeeCategory::class);
    }
    public function students(): BelongsToMany
    {
        return $this->belongsToMany(Student::class, 'student_optional_fees');
    }
}