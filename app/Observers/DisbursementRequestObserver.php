<?php

namespace App\Observers;

use App\Models\DisbursementRequest;
use Illuminate\Support\Facades\Auth;

class DisbursementRequestObserver
{
    /**
     * Handle the DisbursementRequest "created" event.
     */
    public function creating(DisbursementRequest $disbursementRequest): void
    {
        // 2. Otomatis isi 'requester_id' dengan ID user yg sedang login
        if (Auth::check()) {
            $disbursementRequest->requester_id = Auth::id();
        }
    }

    /**
     * Handle the DisbursementRequest "updated" event.
     */
    public function updated(DisbursementRequest $disbursementRequest): void
    {
        //
    }

    /**
     * Handle the DisbursementRequest "deleted" event.
     */
    public function deleted(DisbursementRequest $disbursementRequest): void
    {
        //
    }

    /**
     * Handle the DisbursementRequest "restored" event.
     */
    public function restored(DisbursementRequest $disbursementRequest): void
    {
        //
    }

    /**
     * Handle the DisbursementRequest "force deleted" event.
     */
    public function forceDeleted(DisbursementRequest $disbursementRequest): void
    {
        //
    }
}
