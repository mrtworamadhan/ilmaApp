<?php

namespace App\Filament\Yayasan\Resources\Users\Pages;

use App\Filament\Yayasan\Resources\Users\UserResource;
use Filament\Facades\Filament;
use Filament\Resources\Pages\CreateRecord;
use Illuminate\Database\Eloquent\Model;

class CreateUser extends CreateRecord
{
    protected static string $resource = UserResource::class;
    protected function handleRecordCreation(array $data): Model
    {
        // 1. Ambil data dari form (name, email, password, roles, department_id)
        //    $data SUDAH berisi nilai dari 'UserForm.php'
        
        // 2. Wajib: Sisipkan ID Yayasan (Tenant)
        $data['foundation_id'] = Filament::getTenant()->id;

        // 3. Logika Cerdas untuk 'school_id'
        $loggedInUser = auth()->user();
        
        if ($loggedInUser->school_id !== null) {
            // ========================================================
            // JIKA YANG LOGIN ADALAH ADMIN SEKOLAH
            // ========================================================
            // Paksa 'school_id' user baru = 'school_id' admin sekolah
            // Ini mengabaikan 'school_id' dari form (yang disabled)
            $data['school_id'] = $loggedInUser->school_id;
            
        } else {
            // ========================================================
            // JIKA YANG LOGIN ADALAH ADMIN YAYASAN
            // ========================================================
            // Biarkan data 'school_id' dari form.
            // Jika Admin Yayasan membuat role 'Admin Sekolah', 
            // $data['school_id'] akan berisi ID sekolah yang dipilih.
            // Jika Admin Yayasan membuat role 'Admin Yayasan',
            // $data['school_id'] akan null (karena dropdown-nya di-hide).
            // Ini sudah benar.
        }

        // 4. Buat record
        return static::getModel()::create($data);
    }
    protected function getRedirectUrl(): string
    {
        return $this->getResource()::getUrl('index');
    }
}
