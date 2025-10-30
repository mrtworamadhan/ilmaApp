<?php

namespace App\Filament\Yayasan\Resources\Journals\Pages;

use App\Filament\Yayasan\Resources\Journals\JournalResource;
use Filament\Facades\Filament;
use Filament\Resources\Pages\CreateRecord;

class CreateJournal extends CreateRecord
{
    protected static string $resource = JournalResource::class;
    protected function mutateDataBeforeCreate(array $data): array
    {
        // 1. Sisipkan ID Yayasan
        $data['foundation_id'] = Filament::getTenant()->id;
        
        // 2. Sisipkan siapa yg input
        $data['created_by'] = auth()->user()->id();

        // 3. Jika user Admin Sekolah, pastikan school_id terisi
        if (auth()->user()->school_id) {
             $data['school_id'] = auth()->user()->school_id;
        } 
        // 4. Jika Admin Yayasan, $data['school_id']
        //    sudah terisi (atau null) dari form

        return $data;
    }
}
