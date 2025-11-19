<?php

namespace App\Models;

use App\Observers\BillObserver;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Bill extends Model
{
    use HasFactory;

    protected $fillable = [
        'foundation_id',
        'school_id',
        'student_id',
        // 'fee_category_id',
        // 'amount',
        'total_amount',
        'due_date',
        'month',
        'status',
        'description',
    ];

    /**
     * Get the attributes that should be cast.
     *
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'due_date' => 'date',
        ];
    }
    protected static function boot()
    {
        parent::boot();

        static::observe(BillObserver::class);
    }

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

    // Relasi ke Siswa
    public function student(): BelongsTo
    {
        return $this->belongsTo(Student::class);
    }

    // Relasi ke Kategori Biaya
    // public function feeCategory(): BelongsTo
    // {
    //     return $this->belongsTo(FeeCategory::class);
    // }
    public function items(): HasMany
    {
        return $this->hasMany(BillItem::class);
    }
}