<?php

namespace App\Filament\Yayasan\Resources\AdmissionBatches\Pages;

use App\Filament\Yayasan\Resources\AdmissionBatches\AdmissionBatchResource;
use Filament\Resources\Pages\CreateRecord;

class CreateAdmissionBatch extends CreateRecord
{
    protected static string $resource = AdmissionBatchResource::class;
    protected function mutateDataBeforeCreate(array $data): array
    {
        // 1. foundation_id di-handle otomatis oleh tenant

        // 2. Jika user Admin Sekolah, pastikan school_id terisi
        if (auth()->user()->school_id) {
             $data['school_id'] = auth()->user()->school_id;
        } 
        // 3. Jika Admin Yayasan, $data['school_id']
        //    sudah terisi (atau null) dari form

        return $data;
    }
}
