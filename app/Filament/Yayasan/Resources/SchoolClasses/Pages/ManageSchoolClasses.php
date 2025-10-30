<?php

namespace App\Filament\Yayasan\Resources\SchoolClasses\Pages;

use App\Filament\Yayasan\Resources\SchoolClasses\SchoolClassResource;
use Filament\Actions\CreateAction;
use Filament\Facades\Filament;
use Filament\Resources\Pages\ManageRecords;

class ManageSchoolClasses extends ManageRecords
{
    protected static string $resource = SchoolClassResource::class;

    protected function getHeaderActions(): array
    {
        return [
            CreateAction::make(),
        ];
    }
    protected function mutateDataBeforeCreate(array $data): array
    {
        $data['foundation_id'] = Filament::getTenant()->id;
        
        // Jika school_id belum ada di data (karena disembunyikan),
        // ambil dari user yg login (untuk Admin Sekolah)
        if (empty($data['school_id'])) {
             $data['school_id'] = auth()->user()->school_id;
        }

        return $data;
    }
}
