<?php

namespace App\Observers;

use App\Models\Account;
use App\Models\Journal;
use App\Models\JournalEntry; // <-- 1. PASTIKAN IMPORT INI ADA
use App\Models\SavingAccount;
use App\Models\SavingTransaction;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class SavingTransactionObserver
{
    /**
     * Handle the SavingTransaction "created" event.
     */
    public function created(SavingTransaction $transaction): void
    {
        try {
            DB::transaction(function () use ($transaction) {
                
                // 1. UPDATE SALDO BUKU TABUNGAN
                $savingAccount = $transaction->savingAccount;

                // FIX #1: Cek pakai 'kredit' (lowercase)
                if ($transaction->type === 'kredit') { 
                    $savingAccount->increment('balance', $transaction->amount);
                } 
                // FIX #2: Cek pakai 'debit' (lowercase)
                elseif ($transaction->type === 'debit') { 
                    $savingAccount->decrement('balance', $transaction->amount);
                }

                
                // 2. BUAT JURNAL
                if ($transaction->foundation->hasModule('finance')) {
                    $akunKasTabungan = Account::where('foundation_id', $transaction->foundation_id)
                                    ->where('code', '1105') // Kas Tabungan
                                    ->firstOrFail();
                                    
                    $akunUtangTabungan = Account::where('foundation_id', $transaction->foundation_id)
                                            ->where('code', '2102') // Utang Tabungan Siswa
                                            ->firstOrFail();

                    $debitAccountId = null;
                    $creditAccountId = null;
                    
                    if ($transaction->type === 'kredit') {
                        $debitAccountId = $akunKasTabungan->id;
                        $creditAccountId = $akunUtangTabungan->id;
                    } elseif ($transaction->type === 'debit') {
                        $debitAccountId = $akunUtangTabungan->id;
                        $creditAccountId = $akunKasTabungan->id;
                    }

                    // Jika $debitAccountId masih null (karena type tidak dikenal), 
                    // firstOrFail() di bawah akan gagal dan memicu catch, 
                    // yang mana itu perilaku yg benar.

                    $journal = Journal::create([
                        'foundation_id' => $transaction->foundation_id,
                        'school_id' => $savingAccount->school_id, 
                        'date' => $transaction->created_at->toDateString(),
                        'description' => $transaction->description,
                        'referenceable_id' => $transaction->id,
                        'referenceable_type' => SavingTransaction::class,
                        'created_by' => $transaction->user_id ?? auth()->id(), // Fallback
                    ]);

                    // Entry Sisi DEBIT
                    $journal->entries()->create([
                        'account_id' => $debitAccountId,
                        'type' => 'debit',
                        'amount' => $transaction->amount,
                    ]);

                    // Entry Sisi KREDIT
                    $journal->entries()->create([
                        'account_id' => $creditAccountId,
                        'type' => 'kredit', // FIX #3: Pastikan ini 'kredit' (Bahasa), BUKAN 'credit'
                        'amount' => $transaction->amount,
                    ]);
                }
            });
        } catch (\Exception $e) {
            Log::error("Gagal memproses SavingTransaction Observer (ID: {$transaction->id}): " . $e->getMessage());
            throw $e; 
        }
    }

    /**
     * Handle the SavingTransaction "deleted" event.
     * (PENTING: Kita juga harus handle jika transaksi dibatalkan/dihapus)
     */
    public function deleted(SavingTransaction $transaction): void
    {
        // TODO: Buat logika untuk membatalkan (rollback) saldo dan jurnal
        //       jika sebuah SavingTransaction dihapus.
        //       Untuk saat ini, kita fokus di 'created'.
    }
}