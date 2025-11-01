<?php

namespace App\Filament\Yayasan\Resources\SavingTransactions\Pages;

use App\Filament\Yayasan\Resources\SavingTransactions\SavingTransactionResource;
use Filament\Actions\DeleteAction;
use Filament\Resources\Pages\EditRecord;

class EditSavingTransaction extends EditRecord
{
    protected static string $resource = SavingTransactionResource::class;

    protected function getHeaderActions(): array
    {
        return [
            DeleteAction::make(),
        ];
    }
}
