<?php

namespace App\Observers;

use App\Models\Account; // <-- 1. Import Account
use App\Models\Bill;
use App\Models\Journal; // <-- 2. Import Journal
use App\Models\JournalEntry; // <-- 3. Import JournalEntry
use App\Models\Payment;
use Illuminate\Support\Facades\Log; // <-- 4. Import Log

class PaymentObserver
{
    /**
     * Handle the Payment "created" event.
     * 1. Update status Bill terkait.
     * 2. Buat Jurnal Otomatis (Debit: Kas/Bank, Kredit: Pendapatan).
     */
    public function created(Payment $payment): void
    {
        // Hanya proses jika pembayaran sukses
        if ($payment->status !== 'success') {
            return;
        }

        try {
            // --- Bagian 1: Update Status Bill (Jika pembayaran terkait tagihan) ---
            if ($payment->bill_id) {
                $bill = Bill::find($payment->bill_id);
                if ($bill && $bill->status !== 'paid') {
                    $bill->status = 'paid';
                    $bill->save();
                    Log::info('Status Bill ID: ' . $bill->id . ' diupdate menjadi PAID oleh Payment ID: ' . $payment->id);
                }
            }

            // --- Bagian 2: Buat Jurnal Otomatis ---

            // Tentukan Akun Debit (Kas/Bank) - Ini perlu logika lebih canggih nanti
            // Untuk sekarang, kita asumsikan semua masuk ke akun Kas/Bank pertama yg ditemukan
            // Idealnya, ini harus ditentukan berdasarkan metode bayar atau sekolah
            $akunKasBank = Account::where('foundation_id', $payment->foundation_id)
                     ->where('type', 'aktiva') 
                     // GANTI 'category' MENJADI 'code'
                     ->whereIn('code', ['1101', '1102', '1103']) // 1101=Kas, 1102=Bank BSI, 1103=Bank Xendit
                     ->orderBy('id', 'asc') // Ambil yg pertama dibuat (Kas)
                     ->first();

            if (!$akunKasBank) {
                Log::error('Akun Kas/Bank default tidak ditemukan untuk Foundation ID: ' . $payment->foundation_id . '. Jurnal GAGAL dibuat untuk Payment ID: ' . $payment->id);
                return; // Gagal jika tidak ada akun kas
            }

            // Tentukan Akun Kredit (Pendapatan)
            $akunPendapatan = null;
            $deskripsiJurnal = 'Penerimaan pembayaran ';

            if ($payment->bill_id && $payment->bill->feeCategory && $payment->bill->feeCategory->account) {
                // Jika dari Bill, ambil Akun Pendapatan dari FeeCategory terkait
                $akunPendapatan = $payment->bill->feeCategory->account;
                $deskripsiJurnal .= 'tagihan ' . $payment->bill->feeCategory->name;
            } elseif ($payment->payment_for === 'ppdb') {
                // TODO: Cari Akun Pendapatan PPDB
                $deskripsiJurnal .= 'PPDB';
            } elseif ($payment->payment_for === 'savings_topup') {
                // TODO: Cari Akun Hutang Tabungan Siswa
                $deskripsiJurnal .= 'Top-Up Tabungan';
            } else {
                // Fallback jika tidak jelas
                 Log::warning('Akun Pendapatan tidak bisa ditentukan untuk Payment ID: ' . $payment->id . '. Menggunakan akun default.');
                 // $akunPendapatan = Account::where(...)->first(); // Cari akun pendapatan default
                 $deskripsiJurnal .= 'lain-lain';
            }

             if (!$akunPendapatan) {
                Log::error('Akun Pendapatan/Kredit tidak bisa ditentukan untuk Payment ID: ' . $payment->id . '. Jurnal GAGAL dibuat.');
                 return; // Gagal jika akun kredit tidak ketemu
             }

            $deskripsiJurnal .= ' siswa ' . ($payment->student->name ?? 'N/A');

            // Buat Jurnal Induk
            $journal = Journal::create([
                'foundation_id' => $payment->foundation_id,
                'school_id' => $payment->school_id,
                'date' => $payment->paid_at->toDateString(), // Ambil tanggal dari payment
                'description' => $deskripsiJurnal,
                'referenceable_id' => $payment->id, // Link ke Payment
                'referenceable_type' => Payment::class, // Link ke Payment
                'created_by' => $payment->created_by ?? null, // Siapa yg input (jika manual)
            ]);

            // Buat Entri DEBIT (Kas/Bank bertambah)
            JournalEntry::create([
                'journal_id' => $journal->id,
                'account_id' => $akunKasBank->id,
                'type' => 'debit',
                'amount' => $payment->amount_paid,
            ]);

            // Buat Entri KREDIT (Pendapatan bertambah / Hutang Tabungan bertambah)
            JournalEntry::create([
                'journal_id' => $journal->id,
                'account_id' => $akunPendapatan->id,
                'type' => 'kredit',
                'amount' => $payment->amount_paid,
            ]);

            Log::info('✅ Jurnal Otomatis berhasil dibuat untuk Payment ID: ' . $payment->id . '. Journal ID: ' . $journal->id);


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
