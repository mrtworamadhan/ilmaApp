<?php

namespace App\Filament\Yayasan\Resources\StudentRecords\Pages;

use App\Filament\Yayasan\Resources\StudentRecords\StudentRecordResource;
use Filament\Resources\Pages\CreateRecord;

class CreateStudentRecord extends CreateRecord
{
    protected static string $resource = StudentRecordResource::class;
    protected function mutateDataBeforeCreate(array $data): array
    {
        $data['reported_by_user_id'] = auth()->id();
        if (auth()->user()->school_id) {
             $data['school_id'] = auth()->user()->school_id;
        } 

        return $data;
    }
    
}
