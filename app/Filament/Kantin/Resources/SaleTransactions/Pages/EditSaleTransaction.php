<?php

namespace App\Filament\Kantin\Resources\SaleTransactions\Pages;

use App\Filament\Kantin\Resources\SaleTransactions\SaleTransactionResource;
use Filament\Actions\DeleteAction;
use Filament\Actions\ViewAction;
use Filament\Resources\Pages\EditRecord;

class EditSaleTransaction extends EditRecord
{
    protected static string $resource = SaleTransactionResource::class;

    protected function getHeaderActions(): array
    {
        return [
            ViewAction::make(),
        ];
    }
}
