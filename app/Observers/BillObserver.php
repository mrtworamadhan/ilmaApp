<?php

namespace App\Observers;

use App\Models\Account;
use App\Models\Bill;
use App\Models\Journal;
use App\Models\JournalEntry;
use Illuminate\Support\Facades\Log;
use Illuminate\Contracts\Events\ShouldHandleEventsAfterCommit; // <-- 1. IMPORT INI

// 2. IMPLEMENTS INTERFACE INI
class BillObserver implements ShouldHandleEventsAfterCommit
{
    /**
     * Handle the Bill "created" event.
     * 1. Observer ini 'ShouldHandleEventsAfterCommit' (menunggu DB transaction selesai).
     * 2. Buat SATU Jurnal (Induk).
     * 3. Loop semua 'bill_items' yang baru dibuat.
     * 4. Buat Jurnal Entry (Debit: Piutang, Kredit: Pendapatan) UNTUK SETIAP ITEM.
     */
    public function created(Bill $bill): void
    {
        if (! $bill->foundation->hasModule('finance')) {
            Log::info('Modul Finance nonaktif. Jurnal Akrual DIBATALKAN untuk Bill ID: ' . $bill->id);
            return; 
        }

        $bill->load('items.feeCategory.account'); 

        if ($bill->items->isEmpty()) {
            Log::warning("BillObserver created() dipanggil untuk Bill ID: {$bill->id} tapi tidak ada item. Jurnal di-skip.");
            return;
        }

        Log::info("BillObserver (Gabungan) dipicu untuk Bill ID: {$bill->id} dengan {$bill->items->count()} item.");

        try {
            $akunPiutang = Account::where('foundation_id', $bill->foundation_id)
                                  ->where('system_code', 'piutang_sumbangan_default')
                                  ->first();

            if (!$akunPiutang) {
                Log::error("Akun sistem 'piutang_sumbangan_default' tidak ditemukan. Jurnal GAGAL untuk Bill ID: " . $bill->id);
                return; 
            }

            $journal = Journal::create([
                'foundation_id' => $bill->foundation_id,
                'school_id' => $bill->school_id,
                'date' => $bill->created_at->toDateString(), 
                'description' => $bill->description,
                'referenceable_id' => $bill->id,
                'referenceable_type' => Bill::class,
            ]);

            foreach ($bill->items as $item) {
                
                $akunPendapatan = $item->feeCategory->account ?? null;

                if (!$akunPendapatan) {
                    Log::error("Akun pendapatan tidak ter-mapping di FeeCategory ID: {$item->fee_category_id} (BillItem ID: {$item->id}). Entri Jurnal di-skip.");
                    continue; 
                }

                JournalEntry::create([
                    'journal_id' => $journal->id,
                    'account_id' => $akunPiutang->id,
                    'type' => 'debit',
                    'amount' => $item->amount,
                ]);

                JournalEntry::create([
                    'journal_id' => $journal->id,
                    'account_id' => $akunPendapatan->id,
                    'type' => 'kredit',
                    'amount' => $item->amount,
                ]);
            }

            Log::info('✅ Jurnal Akrual Piutang (Gabungan) berhasil dibuat untuk Bill ID: ' . $bill->id . '. Journal ID: ' . $journal->id);

        } catch (\Exception $e) {
            Log::error('❌ Gagal memproses BillObserver (Gabungan) created() untuk Bill ID: ' . $bill->id, [
                'error' => $e->getMessage(),
            ]);
        }
    }

    /**
     * Handle the Bill "updated" event.
     */
    public function updated(Bill $bill): void
    {
        //
    }

    /**
     * Handle the Bill "deleted" event.
     */
    public function deleted(Bill $bill): void
    {
        // TODO: Handle Jurnal Balik jika Bill (Induk) dihapus
    }
}