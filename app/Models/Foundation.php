<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use App\Models\Pos\Product;
use App\Models\Pos\SaleTransaction;
use App\Models\Pos\Vendor;
use App\Models\Pos\VendorLedger;

class Foundation extends Model
{
    use HasFactory;

    // Menentukan kolom mana yang boleh diisi
    protected $fillable = [
        'name',
        'address',
        'phone',
        'email',
        'npwp',
        'enabled_modules',
    ];

    protected $casts = [
        'enabled_modules' => 'array',
    ];

    /**
     * Definisi relasi: 1 Yayasan punya BANYAK Sekolah
     * Sesuai ERD 
     */
    public function schools(): HasMany
    {
        return $this->hasMany(School::class);
    }

    /**
     * Definisi relasi: 1 Yayasan punya BANYAK User
     */
    public function users(): HasMany
    {
        return $this->hasMany(User::class);
    }

    public function hasModule(string $module): bool
    {
        return in_array($module, $this->enabled_modules ?? []);
    }

    public function vendors(): HasMany
    {
        return $this->hasMany(Vendor::class);
    }

    /**
     * Get all of the products for the Foundation
     */
    public function products(): HasMany
    {
        return $this->hasMany(Product::class);
    }

    /**
     * Get all of the saleTransactions for the Foundation
     */
    public function saleTransactions(): HasMany
    {
        return $this->hasMany(SaleTransaction::class);
    }

    /**
     * Get all of the vendorLedgers for the Foundation
     */
    public function vendorLedgers(): HasMany
    {
        return $this->hasMany(VendorLedger::class);
    }
}