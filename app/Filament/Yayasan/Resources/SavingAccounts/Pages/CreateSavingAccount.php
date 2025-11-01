<?php

namespace App\Filament\Yayasan\Resources\SavingAccounts\Pages;

use App\Filament\Yayasan\Resources\SavingAccounts\SavingAccountResource;
use App\Models\Student; // <-- 1. IMPORT MODEL STUDENT
use Filament\Facades\Filament;
use Filament\Resources\Pages\CreateRecord;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Auth;

class CreateSavingAccount extends CreateRecord
{
    protected static string $resource = SavingAccountResource::class;
    protected function handleRecordCreation(array $data): Model
    {
        // --- 1. Ambil Saldo Awal & Hapus dari data utama ---
        // Kita ingin rekening induk dibuat dgn saldo 0,
        // lalu biarkan Transaksi Saldo Awal yg mengisinya.
        $saldoAwal = $data['balance'] ?? 0;
        unset($data['balance']); 

        // --- 2. Suntikkan ID Tenant & Sekolah ---
        $data['foundation_id'] = Filament::getTenant()->id;

        $student = Student::find($data['student_id']);
        if ($student) {
            $data['school_id'] = $student->school_id;
        } else {
            // Ini seharusnya tidak terjadi jika form validasi
            throw new \Exception("Siswa tidak ditemukan.");
        }

        // --- 3. Buat Rekening Induk (HANYA SEKALI) ---
        // $data sekarang berisi: student_id, account_number, foundation_id, school_id
        $savingAccount = static::getModel()::create($data);

        // --- 4. Buat Transaksi Saldo Awal (jika ada) ---
        if ($saldoAwal > 0) {
            // Transaksi ini akan memicu Observer.
            // Observer akan (1) Update saldo $savingAccount
            // dan (2) Buat Jurnal.
            $savingAccount->transactions()->create([
                'foundation_id' => $savingAccount->foundation_id,
                'type' => 'CREDIT',
                'amount' => $saldoAwal,
                'description' => 'Setoran saldo awal saat pembukaan rekening',
                'user_id' => Auth::id(),
            ]);
        }
        
        // Kembalikan rekening induk yang baru dibuat
        return $savingAccount;
    }
}
