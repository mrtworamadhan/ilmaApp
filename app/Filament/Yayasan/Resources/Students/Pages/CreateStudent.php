<?php

namespace App\Filament\Yayasan\Resources\Students\Pages;

use App\Filament\Yayasan\Resources\Students\StudentResource;
use Filament\Facades\Filament;
use Filament\Resources\Pages\CreateRecord;

class CreateStudent extends CreateRecord
{
    protected static string $resource = StudentResource::class;
    protected function mutateDataBeforeCreate(array $data): array
    {
        // 1. Selalu sisipkan ID Yayasan
        $data['foundation_id'] = Filament::getTenant()->id;
        
        // 2. Jika user adalah Admin Sekolah,
        //    pastikan school_id-nya terisi dari data user-nya
        if (auth()->user()->school_id) {
             $data['school_id'] = auth()->user()->school_id;
        }

        // 3. Jika user Admin Yayasan, $data['school_id']
        //    sudah terisi dari form dropdown

        return $data;
    }
}
