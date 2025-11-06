<?php

namespace App\Filament\Yayasan\Resources\StudentRecords\Pages;

use App\Filament\Yayasan\Resources\StudentRecords\StudentRecordResource;
use Filament\Actions\CreateAction;
use Filament\Resources\Pages\ListRecords;

class ListStudentRecords extends ListRecords
{
    protected static string $resource = StudentRecordResource::class;

    protected function getHeaderActions(): array
    {
        return [
            CreateAction::make(),
        ];
    }
}
