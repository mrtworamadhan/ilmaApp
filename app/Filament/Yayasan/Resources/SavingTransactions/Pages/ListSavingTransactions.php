<?php

namespace App\Filament\Yayasan\Resources\SavingTransactions\Pages;

use App\Filament\Yayasan\Resources\SavingTransactions\SavingTransactionResource;
use Filament\Actions\CreateAction;
use Filament\Resources\Pages\ListRecords;

class ListSavingTransactions extends ListRecords
{
    protected static string $resource = SavingTransactionResource::class;

    protected function getHeaderActions(): array
    {
        return [
            CreateAction::make()->label('Buat Transaksi Baru'),
        ];
    }
}
