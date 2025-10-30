<?php

namespace App\Filament\Yayasan\Resources\Users\Pages;

use App\Filament\Yayasan\Resources\Users\UserResource;
use Filament\Facades\Filament;
use Filament\Resources\Pages\CreateRecord;

class CreateUser extends CreateRecord
{
    protected static string $resource = UserResource::class;
    protected function mutateDataBeforeCreate(array $data): array
    {
        $data['foundation_id'] = Filament::getTenant()->id;
        
        if (auth()->user()->school_id) {
             $data['school_id'] = auth()->user()->school_id;
        } 
        else {
            if (in_array($data['role'], ['admin_yayasan', 'bendahara_yayasan'])) {
                $data['school_id'] = null;
            }
        }

        return $data;
    }
}
