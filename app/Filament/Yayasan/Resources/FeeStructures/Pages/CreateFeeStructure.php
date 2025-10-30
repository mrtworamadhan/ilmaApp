<?php

namespace App\Filament\Yayasan\Resources\FeeStructures\Pages;

use App\Filament\Yayasan\Resources\FeeStructures\FeeStructureResource;
use Filament\Facades\Filament;
use Filament\Resources\Pages\CreateRecord;

class CreateFeeStructure extends CreateRecord
{
    protected static string $resource = FeeStructureResource::class;
    protected function mutateDataBeforeCreate(array $data): array
    {
        $data['foundation_id'] = Filament::getTenant()->id;
        return $data;
    }
}
