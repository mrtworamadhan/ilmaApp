<?php

namespace App\Filament\Yayasan\Resources\AdmissionRegistrations\Pages;

use App\Filament\Yayasan\Resources\AdmissionRegistrations\AdmissionRegistrationResource;
use Filament\Resources\Pages\CreateRecord;

class CreateAdmissionRegistration extends CreateRecord
{
    protected static string $resource = AdmissionRegistrationResource::class;
    protected function mutateDataBeforeCreate(array $data): array
    {
        $data['user_id'] = auth()->id();

        if (auth()->user()->school_id) {
             $data['school_id'] = auth()->user()->school_id;
        } 

        return $data;
    }
}
