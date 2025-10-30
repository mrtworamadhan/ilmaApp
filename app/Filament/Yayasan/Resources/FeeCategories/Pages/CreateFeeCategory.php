<?php

namespace App\Filament\Yayasan\Resources\FeeCategories\Pages;

use App\Filament\Yayasan\Resources\FeeCategories\FeeCategoryResource;
use Filament\Facades\Filament;
use Filament\Resources\Pages\CreateRecord;

class CreateFeeCategory extends CreateRecord
{
    protected static string $resource = FeeCategoryResource::class;
    protected function mutateDataBeforeCreate(array $data): array
    {
        $data['foundation_id'] = Filament::getTenant()->id;
        return $data;
    }
}
