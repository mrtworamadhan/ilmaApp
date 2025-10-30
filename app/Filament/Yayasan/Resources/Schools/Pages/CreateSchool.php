<?php

namespace App\Filament\Yayasan\Resources\Schools\Pages;

use App\Filament\Yayasan\Resources\Schools\SchoolResource;
use Filament\Resources\Pages\CreateRecord;
use Filament\Facades\Filament;

class CreateSchool extends CreateRecord
{
    protected static string $resource = SchoolResource::class;
    protected function mutateDataBeforeCreate(array $data): array
    {
        $data['foundation_id'] = Filament::getTenant()->id;
        
        return $data;
    }
}
