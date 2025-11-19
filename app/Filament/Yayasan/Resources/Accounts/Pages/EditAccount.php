<?php

namespace App\Filament\Yayasan\Resources\Accounts\Pages;

use App\Filament\Yayasan\Resources\Accounts\AccountResource;
use Filament\Actions\DeleteAction;
use Filament\Resources\Pages\EditRecord;

class EditAccount extends EditRecord
{
    protected static string $resource = AccountResource::class;

    protected function getHeaderActions(): array
    {
        return [
            DeleteAction::make(),
        ];
    }
    protected function mutateDataBeforeSave(array $data): array
    {
        dd($data);
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
        return $data; // Kembalikan data yang sudah dimodifikasi
    }
}
