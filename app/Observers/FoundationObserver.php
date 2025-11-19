<?php

namespace App\Observers;

use App\Models\Account;
use App\Models\Foundation;
use Illuminate\Support\Facades\Log;

class FoundationObserver
{
    /**
     * Handle the Foundation "created" event.
     *
     * @param  \App\Models\Foundation  $foundation
     * @return void
     */
    public function created(Foundation $foundation): void
    {
        Log::info("---!!! FOUNDATION OBSERVER 'CREATED' TERPANGGIL UNTUK YAYASAN: " . $foundation->name);
        // Panggil method untuk membuat COA standar ISAK 35
        $this->createIsak35Accounts($foundation);

        // Panggil method untuk membuat Akun Sistem (Tabungan, Kantin, PPDB)
        $this->createSystemAccounts($foundation);
    }

    /**
     * Membuat Chart of Accounts (COA) standar ISAK 35
     * berdasarkan file CSV (akun keuangan.xlsx)
     */
    private function createIsak35Accounts(Foundation $foundation): void
    {
        $foundationId = $foundation->id;
        
        // --- Mapping System Code untuk Akun Krusial ---
        $systemCodeMap = [
            '1111' => 'kas_operasional_default',     // Kas
            '1112' => 'bank_operasional_default',     // Rekening Bank
            '1130' => 'piutang_sumbangan_default',  // Piutang Sumbangan
            '2110' => 'utang_usaha_default',        // Utang Usaha
            '3110' => 'aset_neto_tidak_terikat',    // Aset Neto - Tidak Terikat
            '4110' => 'pendapatan_spp_default',     // Sumbangan Tidak Terikat (kita asumsikan ini SPP)
            '5110' => 'beban_gaji_default',         // Beban Gaji dan Tunjangan
            '5310' => 'beban_penyusutan_default',   // Beban Penyusutan
        ];

        // --- Data Akun dari "akun keuangan.xlsx - Lembar1.csv" ---
        $accounts = [
            // Aset
            ['1111', 'Kas', 'Debit', 'Aset Lancar', 'Aset'],
            ['1112', 'Rekening Bank', 'Debit', 'Aset Lancar', 'Aset'],
            ['1120', 'Investasi Jangka Pendek', 'Debit', 'Aset Lancar', 'Aset'],
            ['1130', 'Piutang Sumbangan', 'Debit', 'Aset Lancar', 'Aset'],
            ['1140', 'Beban Dibayar Dimuka', 'Debit', 'Aset Lancar', 'Aset'],
            ['1210', 'Peralatan', 'Debit', 'Aset Tidak Lancar', 'Aset'],
            ['1220', 'Akumulasi Penyusutan Peralatan', 'Kredit', 'Aset Tidak Lancar', 'Aset'],
            ['1310', 'Tanah', 'Debit', 'Aset Tidak Lancar', 'Aset'],
            ['1320', 'Gedung', 'Debit', 'Aset Tidak Lancar', 'Aset'],
            ['1330', 'Akumulasi Penyusutan Gedung', 'Kredit', 'Aset Tidak Lancar', 'Aset'],
            // Liabilitas
            ['2110', 'Utang Usaha', 'Kredit', 'Kewajiban Jangka Pendek', 'Liabilitas'],
            ['2120', 'Utang Beban', 'Kredit', 'Kewajiban Jangka Pendek', 'Liabilitas'],
            ['2130', 'Pendapatan Diterima Dimuka', 'Kredit', 'Kewajiban Jangka Pendek', 'Liabilitas'],
            ['2210', 'Utang Jangka Panjang', 'Kredit', 'Kewajiban Jangka Panjang', 'Liabilitas'],
            // Aset Neto
            ['3110', 'Aset Neto - Tidak Terikat', 'Kredit', 'Aset Neto', 'Aset Neto'],
            ['3120', 'Aset Neto - Terikat Temporer', 'Kredit', 'Aset Neto', 'Aset Neto'],
            ['3130', 'Aset Neto - Terikat Permanen', 'Kredit', 'Aset Neto', 'Aset Neto'],
            // Pendapatan
            ['4110', 'Sumbangan Tidak Terikat', 'Kredit', 'Laporan Penghasilan Komprehensif', 'Pendapatan'],
            ['4120', 'Sumbangan Terikat Temporer', 'Kredit', 'Laporan Penghasilan Komprehensif', 'Pendapatan'],
            ['4130', 'Sumbangan Terikat Permanen', 'Kredit', 'Laporan Penghasilan Komprehensif', 'Pendapatan'],
            ['4210', 'Pendapatan Investasi', 'Kredit', 'Laporan Penghasilan Komprehensif', 'Pendapatan'],
            ['4310', 'Pendapatan dari Kegiatan Fundraising', 'Kredit', 'Laporan Penghasilan Komprehensif', 'Pendapatan'],
            ['4410', 'Pendapatan Lainnya', 'Kredit', 'Laporan Penghasilan Komprehensif', 'Pendapatan'],
            // Beban
            ['5110', 'Beban Gaji dan Tunjangan', 'Debit', 'Laporan Penghasilan Komprehensif', 'Beban'],
            ['5120', 'Beban Program Sosial', 'Debit', 'Laporan Penghasilan Komprehensif', 'Beban'],
            ['5210', 'Beban Sewa', 'Debit', 'Laporan Penghasilan Komprehensif', 'Beban'],
            ['5220', 'Beban Listrik, Air, dan Telepon', 'Debit', 'Laporan Penghasilan Komprehensif', 'Beban'],
            ['5310', 'Beban Penyusutan', 'Debit', 'Laporan Penghasilan Komprehensif', 'Beban'],
            ['5410', 'Beban Administrasi dan Umum', 'Debit', 'Laporan Penghasilan Komprehensif', 'Beban'],
            ['5510', 'Beban Fundraising', 'Debit', 'Laporan Penghasilan Komprehensif', 'Beban'],
            ['5610', 'Beban Lainnya', 'Debit', 'Laporan Penghasilan Komprehensif', 'Beban'],
        ];

        try {
            foreach ($accounts as $account) {
                $code = $account[0];
                Account::create([
                    'foundation_id'   => $foundationId,
                    'code'            => $code,
                    'name'            => $account[1],
                    'normal_balance'  => $account[2],
                    'category'        => $account[3],
                    'type'            => $account[4],
                    'system_code'     => $systemCodeMap[$code] ?? null,
                ]);
            }
        } catch (\Exception $e) {
            Log::error("Gagal membuat COA standar ISAK 35 untuk Foundation ID: {$foundationId}", [
                'error' => $e->getMessage()
            ]);
        }
    }

    /**
     * Membuat Akun khusus yang dibutuhkan oleh Logika Sistem
     * (Tabungan Siswa, Kantin, dll)
     */
    private function createSystemAccounts(Foundation $foundation): void
    {
        $foundationId = $foundation->id;

        try {
            // Akun untuk Modul Tabungan Siswa
            Account::create([
                'foundation_id'   => $foundationId,
                'code'            => '1105', 
                'name'            => 'Kas Tabungan Siswa',
                'normal_balance'  => 'Debit',
                'category'        => 'Aset Lancar',
                'type'            => 'Aset',
                'system_code'     => 'kas_tabungan_siswa', 
            ]);
            Account::create([
                'foundation_id'   => $foundationId,
                'code'            => '2102', 
                'name'            => 'Utang Tabungan Siswa',
                'normal_balance'  => 'Kredit',
                'category'        => 'Kewajiban Jangka Pendek',
                'type'            => 'Liabilitas',
                'system_code'     => 'utang_tabungan_siswa', 
            ]);

            // Akun untuk Modul Kantin (POS)
            Account::create([
                'foundation_id'   => $foundationId,
                'code'            => '2103', 
                'name'            => 'Utang Vendor Kantin',
                'normal_balance'  => 'Kredit',
                'category'        => 'Kewajiban Jangka Pendek',
                'type'            => 'Liabilitas',
                'system_code'     => 'utang_vendor_default', 
            ]);
            Account::create([
                'foundation_id'   => $foundationId,
                'code'            => '4150', // Kode internal baru
                'name'            => 'Pendapatan Jasa Kantin',
                'normal_balance'  => 'Kredit',
                'category'        => 'Laporan Penghasilan Komprehensif',
                'type'            => 'Pendapatan',
                'system_code'     => 'pendapatan_kantin_default', // (Jika sekolah ambil % fee)
            ]);

            // Akun untuk Modul PPDB
            Account::create([
                'foundation_id'   => $foundationId,
                'code'            => '4140', // Kode internal baru
                'name'            => 'Pendapatan PPDB',
                'normal_balance'  => 'Kredit',
                'category'        => 'Laporan Penghasilan Komprehensif',
                'type'            => 'Pendapatan',
                'system_code'     => 'pendapatan_ppdb_default', // <-- PENAMBAHAN PENTING
            ]);

        } catch (\Exception $e) {
            Log::error("Gagal membuat Akun Sistem untuk Foundation ID: {$foundationId}", [
                'error' => $e->getMessage()
            ]);
        }
    }

    /**
     * Handle the Foundation "updated" event.
     */
    public function updated(Foundation $foundation): void
    {
        //
    }

    /**
     * Handle the Foundation "deleted" event.
     */
    public function deleted(Foundation $foundation): void
    {
        //
    }

    /**
     * Handle the Foundation "restored" event.
     */
    public function restored(Foundation $foundation): void
    {
        //
    }

    /**
     * Handle the Foundation "force deleted" event.
     */
    public function forceDeleted(Foundation $foundation): void
    {
        //
    }
}
