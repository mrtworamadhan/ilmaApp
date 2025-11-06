<?php

namespace App\Filament\Yayasan\Resources\Announcements\Pages;

use App\Filament\Yayasan\Resources\Announcements\AnnouncementResource;
use Filament\Resources\Pages\CreateRecord;

class CreateAnnouncement extends CreateRecord
{
    protected static string $resource = AnnouncementResource::class;
    protected function mutateDataBeforeCreate(array $data): array
    {
        // 1. foundation_id di-handle otomatis oleh tenant
        
        // 2. Sisipkan siapa yg input
        $data['user_id'] = auth()->id();

        // 3. Jika user Admin Sekolah, pastikan school_id terisi
        if (auth()->user()->school_id) {
             $data['school_id'] = auth()->user()->school_id;
        } 
        // 4. Jika Admin Yayasan, $data['school_id']
        //    sudah terisi (atau null) dari form

        return $data;
    }
}
