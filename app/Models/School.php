<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

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

    public function departments(): HasMany
    {
        return $this->hasMany(Department::class);
    }

    public function classes(): HasMany
    {
        return $this->hasMany(Department::class);
    }
    public function admissionBatches(): HasMany
    {
        return $this->hasMany(AdmissionBatch::class);
    }

    /**
     * Definisi relasi: 1 Sekolah punya BANYAK Pendaftar PPDB
     */
    public function admissionRegistrations(): HasMany
    {
        return $this->hasMany(AdmissionRegistration::class);
    }
    public function teacherAttendances(): HasMany
    {
        return $this->hasMany(TeacherAttendance::class);
    }

}