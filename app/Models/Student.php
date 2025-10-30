<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;

class Student extends Model
{
    use HasFactory;

    protected $fillable = [
        'foundation_id',
        'school_id',
        'class_id',
        'parent_id',
        'nis',
        'name',
        'gender',
        'birth_date',
        'va_number',
        'status',
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

    // Relasi ke Kelas
    public function schoolClass(): BelongsTo
    {
        // Arahkan ke model SchoolClass, dengan foreign_key 'class_id'
        return $this->belongsTo(SchoolClass::class, 'class_id'); 
    }

    // Relasi ke User (Orang Tua)
    public function parent(): BelongsTo
    {
        return $this->belongsTo(User::class, 'parent_id');
    }
    public function optionalFees(): BelongsToMany
    {
        return $this->belongsToMany(FeeStructure::class, 'student_optional_fees');
    }
}