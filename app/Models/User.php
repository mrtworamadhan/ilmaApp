<?php

namespace App\Models;

use App\Models\Foundation;
use App\Models\School;
use Filament\Facades\Filament;
use Filament\Panel;
use Filament\Tenancy\Tenant;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Filament\Models\Contracts\HasTenants;
use Illuminate\Support\Facades\Auth;
use Spatie\Permission\Traits\HasRoles;
use Illuminate\Database\Eloquent\Relations\MorphToMany;
use Filament\Models\TenantScope;

class User extends Authenticatable implements HasTenants
{
    use HasFactory, Notifiable;
    use HasRoles;

    protected $fillable = [
        'name',
        'email',
        'password',
        'foundation_id',
        'school_id',
        'department_id',
        'is_platform_admin',
        // 'role',
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

    public function department(): BelongsTo
    {
        return $this->belongsTo(Department::class);
    }
    public function roles(): MorphToMany
    {
        $relation = $this->morphToMany(
            config('permission.models.role'),
            'model',
            config('permission.table_names.model_has_roles'),
            config('permission.column_names.model_morph_key'),
            config('permission.column_names.role_morph_key')
        );

        // GANTI DARI: $relation->withoutGlobalScope(TenantScope::class);
        // MENJADI:
        return $relation->withoutGlobalScopes();
    }
}
