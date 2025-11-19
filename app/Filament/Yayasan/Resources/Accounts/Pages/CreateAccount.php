<?php

namespace App\Filament\Yayasan\Resources\Accounts\Pages;

use App\Filament\Yayasan\Resources\Accounts\AccountResource;
use Filament\Resources\Pages\CreateRecord;
use Filament\Facades\Filament;

class CreateAccount extends CreateRecord
{
    protected static string $resource = AccountResource::class;
    protected function mutateDataBeforeCreate(array $data): array
    {
        dd($data);
        $data['foundation_id'] = Filament::getTenant()->id;
        $type = $data['type']; // Ambil 'Tipe Akun' yang dipilih user

        // Tentukan 'normal_balance' dan 'category' berdasarkan 'type'
        switch ($type) {
            case 'Aset':
                $data['normal_balance'] = 'Debit';
                $data['category'] = 'Aset Lancar'; 
                break;
            case 'Liabilitas':
                $data['normal_balance'] = 'Kredit';
                $data['category'] = 'Kewajiban Jangka Pendek'; 
                break;
            case 'Aset Neto':
                $data['normal_balance'] = 'Kredit';
                $data['category'] = 'Aset Neto';
                break;
            case 'Pendapatan':
                $data['normal_balance'] = 'Kredit';
                $data['category'] = 'Laporan Penghasilan Komprehensif';
                break;
            case 'Beban':
                $data['normal_balance'] = 'Debit';
                $data['category'] = 'Laporan Penghasilan Komprehensif';
                break;
        }
        dd($data);
        return $data;
    }
}
