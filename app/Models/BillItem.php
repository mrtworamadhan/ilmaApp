<?php

namespace App\Models; // Atau App\Models, sesuaikan dengan namespace Anda

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use App\Models\Bill;
use App\Models\FeeCategory;
use App\Models\FeeStructure;

class BillItem extends Model
{
    use HasFactory;

    protected $fillable = [
        'bill_id',
        'fee_structure_id',
        'fee_category_id',
        'description',
        'amount',
    ];

    /**
     * Relasi: Item ini dimiliki oleh satu Tagihan Induk
     */
    public function bill(): BelongsTo
    {
        return $this->belongsTo(Bill::class);
    }

    /**
     * Relasi: Item ini berasal dari satu Kategori Biaya
     */
    public function feeCategory(): BelongsTo
    {
        return $this->belongsTo(FeeCategory::class);
    }

    /**
     * Relasi: Item ini berasal dari satu Aturan Biaya
     */
    public function feeStructure(): BelongsTo
    {
        return $this->belongsTo(FeeStructure::class);
    }
}