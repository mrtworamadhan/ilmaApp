<?php

namespace App\Filament\Yayasan\Resources\DisbursementRequests\Pages;

use App\Filament\Yayasan\Resources\DisbursementRequests\DisbursementRequestResource;
use Filament\Actions\DeleteAction;
use Filament\Resources\Pages\EditRecord;

class EditDisbursementRequest extends EditRecord
{
    protected static string $resource = DisbursementRequestResource::class;

    protected function getHeaderActions(): array
    {
        return [
            DeleteAction::make(),
        ];
    }
}
