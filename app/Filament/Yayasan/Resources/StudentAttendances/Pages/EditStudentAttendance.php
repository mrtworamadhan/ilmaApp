<?php

namespace App\Filament\Yayasan\Resources\StudentAttendances\Pages;

use App\Filament\Yayasan\Resources\StudentAttendances\StudentAttendanceResource;
use Filament\Actions\DeleteAction;
use Filament\Resources\Pages\EditRecord;

class EditStudentAttendance extends EditRecord
{
    protected static string $resource = StudentAttendanceResource::class;

    protected function getHeaderActions(): array
    {
        return [
            DeleteAction::make(),
        ];
    }
}
