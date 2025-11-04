<?php

namespace App\Observers;

use App\Models\Expense;
use App\Models\DisbursementRequest;
use App\Models\Journal; // <-- Import Journal
use App\Models\JournalEntry; // <-- Import JournalEntry
use Illuminate\Support\Facades\Log;

class ExpenseObserver
{
    /**
     * Handle the Expense "created" event.
     */
    public function created(Expense $expense): void
    {
        if ($expense->disbursement_request_id) {
            try {
                $disbursement = DisbursementRequest::find($expense->disbursement_request_id);

                if ($disbursement) {
                    $disbursement->update([
                        'status' => 'DISBURSED', // <-- Update status
                        'realization_amount' => $expense->amount, // <-- Catat jumlah realisasi
                    ]);
                }
            } catch (\Exception $e) {
                Log::error('Gagal update status DisbursementRequest: ' . $e->getMessage());
            }
        }
        if (! $expense->foundation->hasModule('finance')) {
            Log::info('Modul Finance nonaktif. Jurnal DIBATALKAN untuk Expense ID: ' . $expense->id);
            return; // STOP, JANGAN BUAT JURNAL
        }
        Log::info('Modul Finance aktif. Memproses Jurnal untuk Expense ID: ' . $expense->id);
        try {
            // 1. Buat Jurnal Induk
            $journal = $expense->journal()->create([
                'foundation_id' => $expense->foundation_id,
                'school_id' => $expense->school_id,
                'date' => $expense->date,
                'description' => $expense->description,
                'created_by' => $expense->created_by,
            ]);

            // 2. Buat Entri DEBIT (Beban)
            JournalEntry::create([
                'journal_id' => $journal->id,
                'account_id' => $expense->expense_account_id, // <- Akun Beban
                'type' => 'debit',
                'amount' => $expense->amount,
            ]);

            // 3. Buat Entri KREDIT (Kas)
            JournalEntry::create([
                'journal_id' => $journal->id,
                'account_id' => $expense->cash_account_id, // <- Akun Kas/Bank
                'type' => 'kredit',
                'amount' => $expense->amount,
            ]);

        } catch (\Exception $e) {
            // Catat jika ada error
            Log::error('Gagal membuat jurnal otomatis untuk Expense ID: ' . $expense->id, [
                'error' => $e->getMessage()
            ]);
        }
    }

    /**
     * Handle the Expense "updated" event.
     */
    public function updated(Expense $expense): void
    {
        //
    }

    /**
     * Handle the Expense "deleted" event.
     */
    public function deleted(Expense $expense): void
    {
        //
    }

    /**
     * Handle the Expense "restored" event.
     */
    public function restored(Expense $expense): void
    {
        //
    }

    /**
     * Handle the Expense "force deleted" event.
     */
    public function forceDeleted(Expense $expense): void
    {
        //
    }
}
