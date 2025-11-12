<?php

namespace App\Filament\Yayasan\Resources\PayrollComponents\Pages;

use App\Filament\Yayasan\Resources\PayrollComponents\PayrollComponentResource;
use Filament\Facades\Filament;
use Filament\Resources\Pages\CreateRecord;

class CreatePayrollComponent extends CreateRecord
{
    protected static string $resource = PayrollComponentResource::class;
     protected function mutateDataBeforeCreate(array $data): array
    {
        $data['foundation_id'] = Filament::getTenant()->id;
        
        return $data;
    }
}
