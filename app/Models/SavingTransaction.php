<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class SavingTransaction extends Model
{
    use HasFactory;
    protected $guarded = [];
    public function foundation(): BelongsTo
    {
        return $this->belongsTo(Foundation::class);
    }
    public function savingAccount(): BelongsTo
    {
        return $this->belongsTo(SavingAccount::class);
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class); // User (Admin) yg mencatat
    }
}