<?php

namespace App\Models;

use App\Models\Foundation;
use App\Models\School;
use Filament\Panel;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Filament\Models\Contracts\HasTenants; // Hanya ini, tanpa trait

class User extends Authenticatable implements HasTenants
{
    use HasFactory, Notifiable;

    protected $fillable = [
        'name',
        'email',
        'password',
        'foundation_id',
        'school_id',
        'role',
    ];

    protected $hidden = [
        'password',
        'remember_token',
    ];

    protected function casts(): array
    {
        return [
            'email_verified_at' => 'datetime',
            'password' => 'hashed',
        ];
    }

    public function foundation(): BelongsTo
    {
        return $this->belongsTo(Foundation::class);
    }

    public function school(): BelongsTo
    {
        return $this->belongsTo(School::class);
    }

    // ===============================
    // MULTI TENANCY
    // ===============================

    public function getTenants(Panel $panel): \Illuminate\Support\Collection
    {
        // Jika 1 user hanya punya 1 foundation:
        return collect([$this->foundation])->filter();
    }

    public function canAccessTenant(Model $tenant): bool
    {
        return $this->foundation_id === $tenant->id;
    }
}
