<?php

namespace App\Observers;

use App\Models\Account;
use App\Models\Journal;
use App\Models\JournalEntry;
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

                if ($transaction->type === 'kredit') { // 'kredit' (Menabung)
                    $savingAccount->increment('balance', $transaction->amount);
                } 
                elseif ($transaction->type === 'debit') { // 'debit' (Tarik/Belanja)
                    $savingAccount->decrement('balance', $transaction->amount);
                }

                
                // 2. BUAT JURNAL (Jika Modul Finance Aktif)
                if ($transaction->foundation->hasModule('finance')) {

                    // ========================================================
                    // REFACTOR DIMULAI DI SINI
                    // ========================================================

                    // Ganti pencarian 'code' menjadi 'system_code'
                    $akunKasTabungan = Account::where('foundation_id', $transaction->foundation_id)
                                    ->where('system_code', 'kas_tabungan_siswa') // <-- REFACTOR
                                    ->first();
                                    
                    $akunUtangTabungan = Account::where('foundation_id', $transaction->foundation_id)
                                            ->where('system_code', 'utang_tabungan_siswa') // <-- REFACTOR
                                            ->first();

                    // Guard clause jika akun sistem tidak ditemukan
                    if (!$akunKasTabungan || !$akunUtangTabungan) {
                        Log::error("Akun sistem tabungan (kas_tabungan_siswa / utang_tabungan_siswa) tidak ditemukan untuk Foundation ID: {$transaction->foundation_id}. Jurnal DIBATALKAN.");
                        // Batalkan transaksi DB agar saldo tidak terupdate
                        throw new \Exception("Akun sistem tabungan tidak ditemukan."); 
                    }

                    // ========================================================
                    // REFACTOR SELESAI
                    // ========================================================

                    $debitAccountId = null;
                    $creditAccountId = null;
                    
                    if ($transaction->type === 'kredit') { // Menabung
                        // Uang (Aset) bertambah di 'Kas Tabungan Siswa'
                        $debitAccountId = $akunKasTabungan->id;
                        // Utang (Liabilitas) bertambah di 'Utang Tabungan Siswa'
                        $creditAccountId = $akunUtangTabungan->id;
                    } elseif ($transaction->type === 'debit') { // Tarik/Belanja
                        // Utang (Liabilitas) berkurang di 'Utang Tabungan Siswa'
                        $debitAccountId = $akunUtangTabungan->id;
                        // Uang (Aset) berkurang di 'Kas Tabungan Siswa'
                        $creditAccountId = $akunKasTabungan->id;
                    }

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
                        'type' => 'kredit',
                        'amount' => $transaction->amount,
                    ]);
                }
            });
        } catch (\Exception $e) {
            Log::error("Gagal memproses SavingTransaction Observer (ID: {$transaction->id}): " . $e->getMessage());
            // Lemparkan kembali error agar DB::transaction() di-rollback
            throw $e; 
        }
    }

    /**
     * Handle the SavingTransaction "deleted" event.
     */
    public function deleted(SavingTransaction $transaction): void
    {
        // TODO: Buat logika untuk membatalkan (rollback) saldo dan jurnal
        //       jika sebuah SavingTransaction dihapus.
    }
}