<?php

namespace App\Filament\Kantin\Resources\VendorDisbursements\Pages;

use App\Filament\Kantin\Resources\VendorDisbursements\VendorDisbursementResource;
use App\Filament\Kantin\Widgets\KantinSaldoStats;
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
    protected function getHeaderWidgets(): array
    {
        return [
            KantinSaldoStats::class,
        ];
    }
}
