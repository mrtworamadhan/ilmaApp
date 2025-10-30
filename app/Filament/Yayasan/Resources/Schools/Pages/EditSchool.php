<?php

namespace App\Filament\Yayasan\Resources\Schools\Pages;

use App\Filament\Yayasan\Resources\Schools\SchoolResource;
use Filament\Actions\DeleteAction;
use Filament\Resources\Pages\EditRecord;

class EditSchool extends EditRecord
{
    protected static string $resource = SchoolResource::class;

    protected function getHeaderActions(): array
    {
        return [
            DeleteAction::make(),
        ];
    }
}
