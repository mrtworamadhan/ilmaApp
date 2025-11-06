<?php

namespace App\Filament\Yayasan\Resources\AdmissionRegistrations\Pages;

use App\Filament\Yayasan\Resources\AdmissionRegistrations\AdmissionRegistrationResource;
use Filament\Actions\CreateAction;
use Filament\Resources\Pages\ListRecords;

class ListAdmissionRegistrations extends ListRecords
{
    protected static string $resource = AdmissionRegistrationResource::class;

    protected function getHeaderActions(): array
    {
        return [
            CreateAction::make(),
        ];
    }
}
