<?php

namespace App\Observers;

use App\Models\Expense;
use App\Models\DisbursementRequest;
use App\Models\Journal; 
use App\Models\JournalEntry;
use App\Models\Account; // <-- 1. IMPORT ACCOUNT
use Illuminate\Support\Facades\Log;

class ExpenseObserver
{
    /**
     * Handle the Expense "created" event.
     */
    public function created(Expense $expense): void
    {
        // ===================================================
        // BAGIAN 1: UPDATE STATUS DISBURSEMENT (Logika Anda yg sudah ada)
        // ===================================================
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
        
        // ===================================================
        // BAGIAN 2: JURNAL #1 (REALISASI BEBAN) (Logika Anda yg sudah ada)
        // ===================================================
        if (! $expense->foundation->hasModule('finance')) {
            Log::info('Modul Finance nonaktif. Jurnal DIBATALKAN untuk Expense ID: ' . $expense->id);
            return; // STOP, JANGAN BUAT JURNAL
        }
        
        Log::info('Modul Finance aktif. Memproses Jurnal #1 (Realisasi) untuk Expense ID: ' . $expense->id);
        try {
            // 1. Buat Jurnal Induk
            $journal1 = $expense->journal()->create([
                'foundation_id' => $expense->foundation_id,
                'school_id' => $expense->school_id,
                'date' => $expense->date,
                'description' => $expense->description,
                'created_by' => $expense->created_by,
            ]);

            // 2. Buat Entri DEBIT (Beban)
            JournalEntry::create([
                'journal_id' => $journal1->id,
                'account_id' => $expense->expense_account_id, // <- Akun Beban
                'type' => 'debit',
                'amount' => $expense->amount,
            ]);

            // 3. Buat Entri KREDIT (Kas/Bank)
            JournalEntry::create([
                'journal_id' => $journal1->id,
                'account_id' => $expense->cash_account_id, // <- Akun Kas/Bank
                'type' => 'kredit',
                'amount' => $expense->amount,
            ]);
            Log::info('✅ Jurnal #1 (Realisasi) berhasil dibuat.');

        } catch (\Exception $e) {
            Log::error('Gagal membuat Jurnal #1 (Realisasi) untuk Expense ID: ' . $expense->id, [
                'error' => $e->getMessage()
            ]);
        }


        // ===================================================
        // BAGIAN 3: JURNAL #2 (PELEPASAN DANA TERIKAT) (Logika BARU)
        // ===================================================
        // Cek lagi, apakah Expense ini terkait ajuan?
        if ($expense->disbursement_request_id) {
            $this->createRestrictedFundReleaseJournal($expense);
        }
    }


    /**
     * Membuat Jurnal #2 (Jurnal Penyesuaian / Pelepasan Dana Terikat)
     * Ini akan menjurnal:
     * (Debit)   Aset Neto Terikat (misal: Dana BOS)
     * (Kredit)  Aset Neto Tidak Terikat (Default)
     */
    private function createRestrictedFundReleaseJournal(Expense $expense): void
    {
        Log::info('Memeriksa Jurnal #2 (Pelepasan Dana) untuk Expense ID: ' . $expense->id);

        try {
            // 1. Ambil data Budget terkait
            $disbursement = DisbursementRequest::with('budgetItem.budget')
                            ->find($expense->disbursement_request_id);

            if (!$disbursement || !$disbursement->budgetItem || !$disbursement->budgetItem->budget) {
                Log::warning('Data Budget tidak ditemukan, Jurnal #2 dibatalkan.', ['expense_id' => $expense->id]);
                return;
            }

            $budget = $disbursement->budgetItem->budget;

            // 2. Cek apakah ini DANA TERIKAT?
            // Kita cek berdasarkan 'amplop' Aset Neto-nya
            if ($budget->restricted_fund_account_id) {
                
                // 3. Ambil Akun-akun yang dibutuhkan
                $akunDebit_Terikat = Account::find($budget->restricted_fund_account_id);
                
                $akunKredit_TidakTerikat = Account::where('foundation_id', $expense->foundation_id)
                                                ->where('system_code', 'aset_neto_tidak_terikat')
                                                ->first();

                // 4. Validasi
                if (!$akunDebit_Terikat || !$akunKredit_TidakTerikat) {
                    Log::error('Akun Aset Neto (Terikat/Tidak Terikat) tidak ditemukan. Jurnal #2 GAGAL.', [
                        'terikat_id' => $budget->restricted_fund_account_id,
                        'tidak_terikat_system_code' => 'aset_neto_tidak_terikat'
                    ]);
                    return;
                }
                
                Log::info('Ini adalah DANA TERIKAT. Membuat Jurnal #2...');

                // 5. Buat Jurnal Induk (Penyesuaian)
                $journal2 = Journal::create([
                    'foundation_id' => $expense->foundation_id,
                    'school_id' => $expense->school_id,
                    'date' => $expense->date,
                    'description' => "Jurnal Pelepasan Dana Terikat (Ref: " . $expense->description . ")",
                    'referenceable_id' => $expense->id, // Tetap referensi ke Expense
                    'referenceable_type' => Expense::class,
                    'created_by' => $expense->created_by,
                ]);

                // 6. Buat Entri DEBIT (Aset Neto Terikat berkurang)
                JournalEntry::create([
                    'journal_id' => $journal2->id,
                    'account_id' => $akunDebit_Terikat->id,
                    'type' => 'debit',
                    'amount' => $expense->amount,
                ]);

                // 7. Buat Entri KREDIT (Aset Neto Tidak Terikat bertambah)
                JournalEntry::create([
                    'journal_id' => $journal2->id,
                    'account_id' => $akunKredit_TidakTerikat->id,
                    'type' => 'kredit',
                    'amount' => $expense->amount,
                ]);

                Log::info('✅ Jurnal #2 (Pelepasan Dana) berhasil dibuat. Journal ID: ' . $journal2->id);

            } else {
                Log::info('Ini BUKAN Dana Terikat. Jurnal #2 tidak diperlukan.');
            }

        } catch (\Exception $e) {
            Log::error('Gagal membuat Jurnal #2 (Pelepasan Dana) untuk Expense ID: ' . $expense->id, [
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
        // TODO: Handle Jurnal Balik jika Expense dihapus
    }
}