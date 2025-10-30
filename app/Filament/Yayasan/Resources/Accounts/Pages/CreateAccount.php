<?php

namespace App\Filament\Yayasan\Resources\Accounts\Pages;

use App\Filament\Yayasan\Resources\Accounts\AccountResource;
use Filament\Resources\Pages\CreateRecord;
use Filament\Facades\Filament;

class CreateAccount extends CreateRecord
{
    protected static string $resource = AccountResource::class;
    protected function mutateDataBeforeCreate(array $data): array
    {
        $data['foundation_id'] = Filament::getTenant()->id;
        return $data;
    }
}
