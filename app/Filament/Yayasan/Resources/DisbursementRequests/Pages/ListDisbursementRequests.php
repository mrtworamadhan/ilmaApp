<?php

namespace App\Filament\Yayasan\Resources\DisbursementRequests\Pages;

use App\Filament\Yayasan\Resources\DisbursementRequests\DisbursementRequestResource;
use Filament\Actions\CreateAction;
use Filament\Resources\Pages\ListRecords;

class ListDisbursementRequests extends ListRecords
{
    protected static string $resource = DisbursementRequestResource::class;

    protected function getHeaderActions(): array
    {
        return [
            CreateAction::make()
                ->label('Ajukan Pencairan Baru'),
        ];
    }
}
