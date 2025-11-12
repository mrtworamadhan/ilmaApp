<?php

namespace App\Models\Payroll;

use App\Models\Foundation;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use App\Models\Payroll\EmployeePayroll;


class PayrollComponent extends Model
{
    use HasFactory;

    protected $fillable = [
        'foundation_id',
        'name',
        'type',
    ];

    /**
     * Tipe komponen ('allowance' atau 'deduction').
     */
    protected $casts = [
        'type' => 'string', 
    ];

    /**
     * Komponen ini dimiliki oleh Yayasan mana.
     */
    public function foundation(): BelongsTo
    {
        return $this->belongsTo(Foundation::class);
    }
    
    public function employeePayrolls(): HasMany
    {
        return $this->hasMany(EmployeePayroll::class);
    }
}