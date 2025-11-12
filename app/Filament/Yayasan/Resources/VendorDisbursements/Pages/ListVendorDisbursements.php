<?php

namespace App\Filament\Yayasan\Resources\VendorDisbursements\Pages;

use App\Filament\Yayasan\Resources\VendorDisbursements\VendorDisbursementResource;
use Filament\Actions\CreateAction;
use Filament\Resources\Pages\ListRecords;

class ListVendorDisbursements extends ListRecords
{
    protected static string $resource = VendorDisbursementResource::class;

    protected function getHeaderActions(): array
    {
        return [
            CreateAction::make(),
        ];
    }
}
