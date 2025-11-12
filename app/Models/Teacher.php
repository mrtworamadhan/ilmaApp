<?php

namespace App\Models;

use Carbon\Carbon;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;
use Illuminate\Database\Eloquent\Relations\MorphMany;
use App\Models\Payroll\EmployeePayroll;
use App\Models\Payroll\Payslip;

class Teacher extends Model
{
    use HasFactory;

    /**
     * The attributes that are mass assignable.
     *
     * @var array<int, string>
     */
    protected $fillable = [
        'foundation_id',
        'school_id',
        'user_id',
        'nip',
        'full_name',
        'gender',
        'phone',
        'address',
        'photo_path',
        'birth_date',
        'employment_status',
        'education_level',
        'rfid_tag_id',
    ];

    /**
     * Get the attributes that should be cast.
     *
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'birth_date' => 'date',
        ];
    }

    // === RELASI ===

    /**
     * Relasi ke Yayasan (Tenant)
     */
    public function foundation(): BelongsTo
    {
        return $this->belongsTo(Foundation::class);
    }

    /**
     * Relasi ke Sekolah
     */
    public function school(): BelongsTo
    {
        return $this->belongsTo(School::class);
    }

    /**
     * Relasi ke Akun User (jika punya login)
     */
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function attendances(): HasMany
    {
        // Otomatis urutkan dari yang terbaru
        return $this->hasMany(TeacherAttendance::class)->orderBy('date', 'desc');
    }
    public function todaysAttendance(): HasOne
    {
        return $this->hasOne(TeacherAttendance::class)
                    ->whereDate('date', Carbon::today());
    }
    public function payrolls(): HasMany
    {
        return $this->hasMany(EmployeePayroll::class);
    }
    public function payslip(): HasMany
    {
        return $this->hasMany(Payslip::class);
    }
}