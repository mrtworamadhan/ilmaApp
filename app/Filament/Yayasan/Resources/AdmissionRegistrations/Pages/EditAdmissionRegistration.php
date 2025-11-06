<?php

namespace App\Filament\Yayasan\Resources\AdmissionRegistrations\Pages;

use App\Filament\Yayasan\Resources\AdmissionRegistrations\AdmissionRegistrationResource;
use Filament\Actions\DeleteAction;
use Filament\Resources\Pages\EditRecord;

class EditAdmissionRegistration extends EditRecord
{
    protected static string $resource = AdmissionRegistrationResource::class;

    protected function getHeaderActions(): array
    {
        return [
            DeleteAction::make(),
        ];
    }
}
