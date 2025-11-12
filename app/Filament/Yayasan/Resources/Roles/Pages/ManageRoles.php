<?php

namespace App\Filament\Yayasan\Resources\Roles\Pages;

use App\Filament\Yayasan\Resources\Roles\RoleResource;
use Filament\Actions\CreateAction;
use Filament\Resources\Pages\ManageRecords;

class ManageRoles extends ManageRecords
{
    protected static string $resource = RoleResource::class;

    // protected function getHeaderActions(): array
    // {
    //     return [
    //         CreateAction::make(),
    //     ];
    // }
    protected function getRedirectUrl(): string
    {
        return $this->getResource()::getUrl('index');
    }
}
