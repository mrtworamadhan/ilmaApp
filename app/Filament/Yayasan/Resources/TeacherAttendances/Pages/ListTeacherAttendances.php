<?php

namespace App\Filament\Yayasan\Resources\TeacherAttendances\Pages;

use App\Filament\Yayasan\Resources\TeacherAttendances\TeacherAttendanceResource;
use Filament\Actions\CreateAction;
use Filament\Resources\Pages\ListRecords;

class ListTeacherAttendances extends ListRecords
{
    protected static string $resource = TeacherAttendanceResource::class;

    protected function getHeaderActions(): array
    {
        return [
            CreateAction::make(),
        ];
    }
}
