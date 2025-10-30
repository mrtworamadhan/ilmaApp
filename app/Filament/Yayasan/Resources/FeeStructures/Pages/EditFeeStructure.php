<?php

namespace App\Filament\Yayasan\Resources\FeeStructures\Pages;

use App\Filament\Yayasan\Resources\FeeStructures\FeeStructureResource;
use Filament\Actions\DeleteAction;
use Filament\Resources\Pages\EditRecord;

class EditFeeStructure extends EditRecord
{
    protected static string $resource = FeeStructureResource::class;

    protected function getHeaderActions(): array
    {
        return [
            DeleteAction::make(),
        ];
    }
}
