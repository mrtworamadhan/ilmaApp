<?php

namespace App\Filament\Yayasan\Resources\Vendors\Pages;

use App\Filament\Yayasan\Resources\Vendors\VendorResource;
use Filament\Resources\Pages\CreateRecord;
use App\Models\User;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Auth;

class CreateVendor extends CreateRecord
{
    protected static string $resource = VendorResource::class;
    protected function mutateFormDataBeforeCreate(array $data): array
    {
        // 1. Buat User baru dari data form "fiktif"
        $user = User::create([
            'name' => $data['user_name'],
            'email' => $data['user_email'],
            'password' => Hash::make($data['user_password']),
            'foundation_id' => Auth::user()->foundation_id, 
            'school_id' => $data['school_id'],
        ]);

        // 2. Beri role "Kasir Kantin"
        $user->assignRole('Kasir Kantin');

        // 3. Suntik 'user_id' baru ke data utama
        $data['user_id'] = $user->id;

        // 4. Hapus data fiktif agar tidak error saat create Vendor
        unset($data['user_name']);
        unset($data['user_email']);
        unset($data['user_password']);

        // 5. Kembalikan data yang sudah bersih
        return $data;
    }
}
