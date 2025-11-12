<?php

namespace App\Filament\Kantin\Resources\SaleTransactions\Pages;

use App\Filament\Kantin\Resources\SaleTransactions\SaleTransactionResource;
use Filament\Actions\CreateAction;
use Filament\Resources\Pages\ListRecords;

class ListSaleTransactions extends ListRecords
{
    protected static string $resource = SaleTransactionResource::class;

    protected function getHeaderActions(): array
    {
        return [
            //
        ];
    }
}
