<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class AdmissionRegistration extends Model
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
        'status',
        'registration_wave',
        'registration_number',
        'full_name',
        'gender',
        'birth_place',
        'birth_date',
        'religion',
        'previous_school',
        'parent_name',
        'parent_phone',
        'parent_email',
        'payment_proof_path',
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
}