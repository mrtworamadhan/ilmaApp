<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Filament\Facades\Filament;
use Illuminate\Database\Eloquent\Relations\HasManyThrough;
use App\Models\Expense;
use App\Models\DisbursementRequest;

class BudgetItem extends Model
{
    use HasFactory;
    protected $guarded = [];
    
    public function foundation(): BelongsTo
    {
        return $this->belongsTo(Foundation::class);
    }

    public function budget(): BelongsTo
    {
        return $this->belongsTo(Budget::class);
    }

    public function account(): BelongsTo
    {
        return $this->belongsTo(Account::class);
    }
    protected static function booted(): void
    {
        static::creating(function (BudgetItem $budgetItem) {
            if (Filament::getTenant()) {
                $budgetItem->foundation_id = Filament::getTenant()->id;
            }
        });
    }
    public function expenses(): HasManyThrough
    {
        return $this->hasManyThrough(
            Expense::class,
            DisbursementRequest::class,
            'budget_item_id',
            'disbursement_request_id', 
            'id', 
            'id' 
        );
    }
    
}