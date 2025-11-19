<?php

namespace App\Observers;

use App\Models\Account;
use App\Models\Bill;
use App\Models\Journal;
use App\Models\JournalEntry;
use App\Models\Payment;
use Illuminate\Support\Facades\Log;

class PaymentObserver
{
    /**
     * Handle the Payment "created" event.
     * LOGIKA BARU (AKRUAL):
     * 1. Jika Payment terkait Bill:
     * Jurnal: (D) Kas, (K) Piutang
     * 2. Jika Payment non-Bill (PPDB, Topup):
     * Jurnal: (D) Kas, (K) Pendapatan/Utang
     */
    public function created(Payment $payment): void
    {
        if ($payment->status !== 'success') {
            return;
        }

        try {
            if ($payment->bill_id) {
                $bill = Bill::find($payment->bill_id);
                if ($bill && $bill->status !== 'paid') {
                    $bill->status = 'paid'; 
                    $bill->save();
                    Log::info('Status Bill ID: ' . $bill->id . ' diupdate menjadi PAID oleh Payment ID: ' . $payment->id);
                }
            }
            if (! $payment->foundation->hasModule('finance')) {
                Log::info('Modul Finance nonaktif. Jurnal DIBATALKAN untuk Payment ID: ' . $payment->id);
                return;
            }

            $akunKasBank = Account::where('foundation_id', $payment->foundation_id)
                             ->where('system_code', 'kas_operasional_default')
                             ->first();

            if (!$akunKasBank) {
                Log::error('Akun Kas/Bank default (kas_operasional_default) tidak ditemukan. Jurnal GAGAL untuk Payment ID: ' . $payment->id);
                return; 
            }
            
            $akunKredit = null; 
            $deskripsiJurnal = 'Penerimaan pembayaran ';

            $akunPiutang = Account::where('foundation_id', $payment->foundation_id)
                                  ->where('system_code', 'piutang_sumbangan_default')
                                  ->first();

            if ($payment->bill_id) {
                if (!$akunPiutang) {
                    Log::error("Akun sistem 'piutang_sumbangan_default' tidak ditemukan. Jurnal GAGAL untuk Payment ID: " . $payment->id);
                    return;
                }
                
                $akunKredit = $akunPiutang;
                
                $deskripsiJurnal .= 'pelunasan ' . ($payment->bill->description ?? 'tagihan'); // <-- INI BENAR
            
            } elseif ($payment->payment_for === 'ppdb') {
                $akunKredit = Account::where('foundation_id', $payment->foundation_id)
                                    ->where('system_code', 'pendapatan_ppdb_default')
                                    ->first();
                $deskripsiJurnal .= 'PPDB';

            } elseif ($payment->payment_for === 'savings_topup') {
                $akunKredit = Account::where('foundation_id', $payment->foundation_id)
                                    ->where('system_code', 'utang_tabungan_siswa')
                                    ->first();
                $deskripsiJurnal .= 'Top-Up Tabungan';

            } else {
                $deskripsiJurnal .= 'lain-lain';
            }

             if (!$akunKredit) {
                Log::error('Akun Kredit (Piutang/Pendapatan/Utang) tidak bisa ditentukan. Jurnal GAGAL untuk Payment ID: ' . $payment->id);
                 return; 
             }

            $deskripsiJurnal .= ' siswa ' . ($payment->student->name ?? 'N/A'); // <-- 'name' BUKAN 'full_name'

            $journal = Journal::create([
                'foundation_id' => $payment->foundation_id,
                'school_id' => $payment->school_id,
                'date' => $payment->paid_at->toDateString(),
                'description' => $deskripsiJurnal,
                'referenceable_id' => $payment->id,
                'referenceable_type' => Payment::class,
                'created_by' => $payment->created_by ?? null,
            ]);

            JournalEntry::create([
                'journal_id' => $journal->id,
                'account_id' => $akunKasBank->id,
                'type' => 'debit',
                'amount' => $payment->amount_paid,
            ]);

            JournalEntry::create([
                'journal_id' => $journal->id,
                'account_id' => $akunKredit->id,
                'type' => 'kredit',
                'amount' => $payment->amount_paid,
            ]);

            Log::info('✅ Jurnal Pelunasan Piutang/Pembayaran berhasil dibuat untuk Payment ID: ' . $payment->id . '. Journal ID: ' . $journal->id);

        } catch (\Exception $e) {
            Log::error('❌ Gagal memproses PaymentObserver created() untuk Payment ID: ' . $payment->id, [
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
            ]);
        }
    }

    /**
     * Handle the Payment "updated" event.
     */
    public function updated(Payment $payment): void
    {
        //
    }

    /**
     * Handle the Payment "deleted" event.
     */
    public function deleted(Payment $payment): void
    {
        //
    }

    /**
     * Handle the Payment "restored" event.
     */
    public function restored(Payment $payment): void
    {
        //
    }

    /**
     * Handle the Payment "force deleted" event.
     */
    public function forceDeleted(Payment $payment): void
    {
        //
    }
}