<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use App\Models\Pos\Product;
use App\Models\Pos\SaleTransaction;
use App\Models\Pos\Vendor;
use App\Models\Pos\VendorLedger;

class School extends Model
{
    use HasFactory, HasUuids;

    protected $fillable = [
        'foundation_id',
        'name',
        'uuid',
        'api_key',
        'level',
        'address',
        'phone',
        'headmaster',
        'teacher_check_in_time',
        'teacher_check_out_time',
    ];
    public function getRouteKeyName()
    {
        return 'uuid';
    }

    /**
     * Definisi relasi: 1 Sekolah milik 1 Yayasan
     * Sesuai ERD 
     */
    public function foundation(): BelongsTo
    {
        return $this->belongsTo(Foundation::class);
    }

    public function departments(): HasMany
    {
        return $this->hasMany(Department::class);
    }

    public function classes(): HasMany
    {
        return $this->hasMany(Department::class);
    }
    public function admissionBatches(): HasMany
    {
        return $this->hasMany(AdmissionBatch::class);
    }

    /**
     * Definisi relasi: 1 Sekolah punya BANYAK Pendaftar PPDB
     */
    public function admissionRegistrations(): HasMany
    {
        return $this->hasMany(AdmissionRegistration::class);
    }
    public function teacherAttendances(): HasMany
    {
        return $this->hasMany(TeacherAttendance::class);
    }

    public function vendors(): HasMany
    {
        return $this->hasMany(Vendor::class);
    }

    /**
     * Get all of the products for the School
     */
    public function products(): HasMany
    {
        return $this->hasMany(Product::class);
    }

    /**
     * Get all of the saleTransactions for the School
     */
    public function saleTransactions(): HasMany
    {
        return $this->hasMany(SaleTransaction::class);
    }

    /**
     * Get all of the vendorLedgers for the School
     */
    public function vendorLedgers(): HasMany
    {
        return $this->hasMany(VendorLedger::class);
    }

}