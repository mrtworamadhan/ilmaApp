<?php

namespace App\Filament\Kantin\Resources\Products\Pages;

use App\Filament\Kantin\Resources\Products\ProductResource;
use Filament\Resources\Pages\CreateRecord;
use Illuminate\Support\Facades\Auth;

class CreateProduct extends CreateRecord
{
    protected static string $resource = ProductResource::class;
    protected function mutateFormDataBeforeCreate(array $data): array
    {
        $user = Auth::user();
        $vendor = $user->vendor;

        if ($vendor) {
            $data['vendor_id'] = $vendor->id;
            $data['school_id'] = $vendor->school_id;
            $data['foundation_id'] = $vendor->foundation_id;
        }

        return $data;
    }
    
}
