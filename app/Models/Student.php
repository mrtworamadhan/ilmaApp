<?php

namespace App\Models;

use Carbon\Carbon;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasOne;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Student extends Model
{
    use HasFactory;

    protected $fillable = [
        'foundation_id',
        'school_id',
        'class_id',
        'parent_id',
        'nis',
        'nisn', 
        'full_name',
        'nickname', 
        'gender',
        'birth_place', 
        'birth_date',
        'religion', 
        'citizenship', 
        'child_order', 
        'siblings_count', 
        'address', 
        'phone', 
        'photo_path', 
        'father_name', 
        'father_education', 
        'father_job', 
        'mother_name', 
        'mother_education', 
        'mother_job', 
        'guardian_name', 
        'guardian_relationship', 
        'guardian_address', 
        'guardian_phone', 
        'va_number',
        'status',
        'rfid_tag_id',
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

    public function schoolClass(): BelongsTo
    {
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
    public function savingAccount(): HasOne
    {
        return $this->hasOne(SavingAccount::class);
    }
    public function studentRecords(): HasMany
    {
        // Otomatis urutkan dari yang terbaru
        return $this->hasMany(StudentRecord::class)->orderBy('date', 'desc');
    }
    public function attendances(): HasMany
    {
        // Otomatis urutkan dari yang terbaru
        return $this->hasMany(StudentAttendance::class)->orderBy('date', 'desc');
    }
    public function todaysAttendance(): HasOne
    {
        return $this->hasOne(StudentAttendance::class)
                    ->whereDate('date', Carbon::today());
    }
}