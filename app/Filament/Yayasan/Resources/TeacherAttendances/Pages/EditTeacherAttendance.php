<?php

namespace App\Filament\Yayasan\Resources\TeacherAttendances\Pages;

use App\Filament\Yayasan\Resources\TeacherAttendances\TeacherAttendanceResource;
use Filament\Actions\DeleteAction;
use Filament\Resources\Pages\EditRecord;

class EditTeacherAttendance extends EditRecord
{
    protected static string $resource = TeacherAttendanceResource::class;

    protected function getHeaderActions(): array
    {
        return [
            DeleteAction::make(),
        ];
    }
}
