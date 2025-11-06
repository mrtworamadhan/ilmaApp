<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Announcement extends Model
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
        'title',
        'content',
        'target_roles',
        'status',
        'published_at',
    ];

    /**
     * Get the attributes that should be cast.
     *
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'published_at' => 'datetime', // Otomatis konversi ke objek Carbon
            'target_roles' => 'array',     // Otomatis konversi JSON ke array
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
     * Relasi ke Sekolah (bisa null)
     */
    public function school(): BelongsTo
    {
        return $this->belongsTo(School::class);
    }

    /**
     * Relasi ke User (pembuat pengumuman)
     */
    public function creator(): BelongsTo
    {
        // 'user_id' adalah foreign key di tabel 'announcements'
        return $this->belongsTo(User::class, 'user_id');
    }
}