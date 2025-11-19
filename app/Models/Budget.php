<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Budget extends Model
{
    use HasFactory;
    protected $guarded = [];

    public function foundation(): BelongsTo
    {
        return $this->belongsTo(Foundation::class);
    }
    public function department(): BelongsTo
    {
        return $this->belongsTo(Department::class);
    }

    public function items(): HasMany
    {
        return $this->hasMany(BudgetItem::class);
    }
    public function cashSourceAccount(): BelongsTo
    {
        return $this->belongsTo(Account::class, 'cash_source_account_id');
    }


    public function restrictedFundAccount(): BelongsTo
    {
        return $this->belongsTo(Account::class, 'restricted_fund_account_id');
    }
}