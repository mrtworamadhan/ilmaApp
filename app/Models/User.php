<?php

namespace App\Models;

use App\Models\Foundation;
use App\Models\School;
use App\Models\Pos\Vendor;
use Filament\Facades\Filament;
use Filament\Panel;
use Filament\Tenancy\Tenant;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;
use Illuminate\Database\Eloquent\Relations\MorphMany;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Filament\Models\Contracts\HasTenants;
use Illuminate\Support\Facades\Auth;
use Spatie\Permission\Traits\HasRoles;
use Illuminate\Database\Eloquent\Relations\MorphToMany;
use Filament\Models\TenantScope;
use App\Models\Payroll\EmployeePayroll;

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

        return $relation->withoutGlobalScopes();
    }
    
    public function reportedStudentRecords(): HasMany
    {
        return $this->hasMany(StudentRecord::class, 'reported_by_user_id');
    }
    public function reportedAttendances(): HasMany
    {
        return $this->hasMany(StudentAttendance::class, 'reported_by_user_id');
    }
    public function reportedTeacherAttendances(): HasMany
    {
        return $this->hasMany(TeacherAttendance::class, 'reported_by_user_id');
    }
    public function vendor(): HasOne
    {
        return $this->hasOne(Vendor::class);
    }
    public function payrolls(): MorphMany
    {
        // Relasi polimorfik ke tabel employee_payrolls
        return $this->morphMany(EmployeePayroll::class, 'employable');
    }
}
