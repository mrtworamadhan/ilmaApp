<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class TeacherAttendance extends Model
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
        'teacher_id',
        'reported_by_user_id',
        'date',
        'status',
        'timestamp_in',
        'timestamp_out',
        'method',
        'notes',
    ];

    /**
     * Get the attributes that should be cast.
     *
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'date' => 'date',
            // Kita cast 'time' sebagai datetime H:i:s
            // 'timestamp_in' => 'datetime:H:i:s', 
            // 'timestamp_out' => 'datetime:H:i:s',
            // Atau biarkan sebagai string, tergantung kebutuhan Anda
        ];
    }

    // === RELASI ===

    public function foundation(): BelongsTo
    {
        return $this->belongsTo(Foundation::class);
    }

    public function school(): BelongsTo
    {
        return $this->belongsTo(School::class);
    }

    /**
     * Relasi ke Guru (yang diabsen)
     */
    public function teacher(): BelongsTo
    {
        return $this->belongsTo(Teacher::class);
    }

    /**
     * Relasi ke User (yang melapor/input)
     */
    public function reporter(): BelongsTo
    {
        return $this->belongsTo(User::class, 'reported_by_user_id');
    }
}