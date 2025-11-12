<?php

namespace App\Filament\Kantin\Resources\SaleTransactions\Pages;

use App\Filament\Kantin\Resources\SaleTransactions\SaleTransactionResource;
use Filament\Actions\EditAction;
use Filament\Resources\Pages\ViewRecord;

class ViewSaleTransaction extends ViewRecord
{
    protected static string $resource = SaleTransactionResource::class;

    protected function getHeaderActions(): array
    {
        return [
            //
        ];
    }
}
