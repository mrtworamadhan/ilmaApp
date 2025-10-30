<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class SchoolClass extends Model // <- Nama model sudah benar
{
    use HasFactory;
    
    // INI KUNCINYA: 
    // Beritahu Laravel nama tabel databasenya
    protected $table = 'classes'; 

    protected $fillable = [
        'foundation_id',
        'school_id',
        'name',
        'homeroom_teacher_id',
        'grade_level'
    ];

    // (Semua relasi lainnya sama, tidak perlu diubah)
    public function foundation(): BelongsTo
    {
        return $this->belongsTo(Foundation::class);
    }

    public function school(): BelongsTo
    {
        return $this->belongsTo(School::class);
    }

    public function homeroomTeacher(): BelongsTo
    {
        return $this->belongsTo(User::class, 'homeroom_teacher_id');
    }
}