<?php

namespace App\Filament\Yayasan\Resources\SavingAccounts\Pages;

use App\Filament\Yayasan\Resources\SavingAccounts\SavingAccountResource;
use Filament\Actions\CreateAction;
use Filament\Resources\Pages\ListRecords;

class ListSavingAccounts extends ListRecords
{
    protected static string $resource = SavingAccountResource::class;

    protected function getHeaderActions(): array
    {
        return [
            CreateAction::make()->label('Buka Rekening'),
        ];
    }
}
