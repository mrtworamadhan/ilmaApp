<?php

namespace App\Filament\Kantin\Resources\VendorDisbursements\Pages;

use App\Filament\Kantin\Resources\VendorDisbursements\VendorDisbursementResource;
use Filament\Actions\DeleteAction;
use Filament\Resources\Pages\EditRecord;

class EditVendorDisbursement extends EditRecord
{
    protected static string $resource = VendorDisbursementResource::class;

    protected function getHeaderActions(): array
    {
        return [
            DeleteAction::make()->label('Batalkan Pengajuan'),
        ];
    }
}
