<?php

namespace App\Filament\Kantin\Resources\VendorDisbursements\Pages;

use App\Filament\Kantin\Resources\VendorDisbursements\VendorDisbursementResource;
use App\Filament\Kantin\Widgets\KantinSaldoStats;
use Filament\Resources\Pages\CreateRecord;
use Illuminate\Support\Facades\Auth;

class CreateVendorDisbursement extends CreateRecord
{
    protected static string $resource = VendorDisbursementResource::class;
    protected function mutateFormDataBeforeCreate(array $data): array
    {
        $user = Auth::user();
        $vendor = $user->vendor;

        if ($vendor) {
            // Suntik data tenant
            $data['vendor_id'] = $vendor->id;
            $data['school_id'] = $vendor->school_id;
            $data['foundation_id'] = $vendor->foundation_id;
            
            // Set status awal
            $data['status'] = 'requested';
        }

        return $data;
    }
    protected function getHeaderWidgets(): array
    {
        return [
            KantinSaldoStats::class,
        ];
    }
}
