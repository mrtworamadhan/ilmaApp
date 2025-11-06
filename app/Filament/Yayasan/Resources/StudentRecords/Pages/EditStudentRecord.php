<?php

namespace App\Filament\Yayasan\Resources\StudentRecords\Pages;

use App\Filament\Yayasan\Resources\StudentRecords\StudentRecordResource;
use Filament\Actions\DeleteAction;
use Filament\Resources\Pages\EditRecord;

class EditStudentRecord extends EditRecord
{
    protected static string $resource = StudentRecordResource::class;

    protected function getHeaderActions(): array
    {
        return [
            DeleteAction::make(),
        ];
    }
}
