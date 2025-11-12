<?php

namespace App\Models\Pos;

use App\Models\Foundation;
use App\Models\School;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\MorphTo;

class SaleTransaction extends Model
{
    use HasFactory;

    protected $fillable = [
        'foundation_id',
        'school_id',
        'vendor_id',
        'buyer_id',
        'buyer_type',
        'transaction_code',
        'total_amount',
        'items',
    ];

    protected $casts = [
        'total_amount' => 'decimal:2',
        'items' => 'array', // Otomatis cast JSON ke array & sebaliknya
    ];

    public function foundation(): BelongsTo
    {
        return $this->belongsTo(Foundation::class);
    }

    public function school(): BelongsTo
    {
        return $this->belongsTo(School::class);
    }

    public function vendor(): BelongsTo
    {
        return $this->belongsTo(Vendor::class);
    }

    public function buyer(): MorphTo
    {
        return $this->morphTo();
    }
}