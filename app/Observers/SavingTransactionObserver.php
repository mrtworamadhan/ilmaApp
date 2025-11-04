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
                
                // 1. UPDATE SALDO BUKU TABUNGAN (Ini sudah benar)
                $savingAccount = $transaction->savingAccount;

                if ($transaction->type === 'CREDIT') {
                    $savingAccount->increment('balance', $transaction->amount);
                } elseif ($transaction->type === 'DEBIT') {
                    $savingAccount->decrement('balance', $transaction->amount);
                }

                
                // 2. BUAT JURNAL (VERSI DOUBLE ENTRY YANG BENAR)
                if ($transaction->foundation->hasModule('finance')) {
                    $akunKasTabungan = Account::where('foundation_id', $transaction->foundation_id)
                                ->where('code', '1105') // <-- GANTI DARI 1101 ke 1105
                                ->firstOrFail();
                                
                    $akunUtangTabungan = Account::where('foundation_id', $transaction->foundation_id)
                                    ->where('code', '2102') // Asumsi 2102 = Utang Tabungan Siswa
                                    ->firstOrFail();

                    $debitAccountId = null;
                    $creditAccountId = null;
                    
                    if ($transaction->type === 'CREDIT') {
                        // SETOR: Kas (Debit), Utang Tabungan (Kredit)
                        $debitAccountId = $akunKasTabungan->id;
                        $creditAccountId = $akunUtangTabungan->id;
                    } elseif ($transaction->type === 'DEBIT') {
                        // TARIK: Utang Tabungan (Debit), Kas (Kredit)
                        $debitAccountId = $akunUtangTabungan->id;
                        $creditAccountId = $akunKasTabungan->id;
                    }

                    // ===============================================
                    // PERBAIKAN LOGIKA JURNAL
                    // ===============================================

                    // Langkah A: Buat Jurnal HEADER
                    $journal = Journal::create([
                        'foundation_id' => $transaction->foundation_id,
                        'school_id' => $savingAccount->school_id, // <-- FIX 1: Ambil school_id dari rekening
                        'date' => $transaction->created_at->toDateString(),
                        'description' => $transaction->description,
                        'referenceable_id' => $transaction->id,
                        'referenceable_type' => SavingTransaction::class,
                        'created_by' => $transaction->user_id,
                    ]);

                    // Langkah B: Buat Jurnal ENTRIES (Detail Debit & Kredit)
                    
                    // Entry Sisi DEBIT
                    $journal->entries()->create([
                        'account_id' => $debitAccountId,
                        'type' => 'debit', // <-- Sesuai LaporanNeraca.php Anda
                        'amount' => $transaction->amount,
                    ]);

                    // Entry Sisi KREDIT
                    $journal->entries()->create([
                        'account_id' => $creditAccountId,
                        'type' => 'kredit', // <-- Sesuai LaporanNeraca.php Anda
                        'amount' => $transaction->amount,
                    ]);
                } else {
                    // Jika modul 'finance' nonaktif, jangan buat jurnal
                    Log::info('Modul Finance nonaktif. Jurnal DIBATALKAN untuk SavingTransaction ID: ' . $transaction->id);
                }

            });
        } catch (\Exception $e) {
            Log::error("Gagal memproses SavingTransaction Observer (ID: {$transaction->id}): " . $e->getMessage());
            $transaction->delete();
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