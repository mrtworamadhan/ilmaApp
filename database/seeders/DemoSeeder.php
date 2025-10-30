<?php

namespace Database\Seeders;

use App\Models\Account;
use App\Models\FeeCategory;
use App\Models\FeeStructure;
use App\Models\Foundation;
use App\Models\School;
use App\Models\SchoolClass;
use App\Models\Student;    
use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash; 

class DemoSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        // 1. Buat 1 Yayasan (Tenant)
        $foundation = Foundation::create([
            'name' => 'Yayasan Demo ILMA',
            'email' => 'kontak@yayasan-demo.com',
            'phone' => '08123456789'
        ]);

        // 2. Buat 2 Sekolah (SD dan TK)
        $schoolSD = School::create([
            'foundation_id' => $foundation->id,
            'name' => 'SD Demo ILMA',
            'level' => 'sd',
            'headmaster' => 'Bpk. Budi'
        ]);
        
        $schoolTK = School::create([
            'foundation_id' => $foundation->id,
            'name' => 'TK Demo ILMA',
            'level' => 'tk',
            'headmaster' => 'Ibu. Ani'
        ]);

        // 3. Buat User Admin
        User::create([
            'name' => 'Admin Yayasan Demo',
            'email' => 'admin@yayasan.com',
            'password' => Hash::make('password'), 
            'foundation_id' => $foundation->id,
            'school_id' => null, 
            'role' => 'admin_yayasan'
        ]);

        User::create([
            'name' => 'Admin SD Demo',
            'email' => 'admin@sd.com',
            'password' => Hash::make('password'), 
            'foundation_id' => $foundation->id,
            'school_id' => $schoolSD->id, 
            'role' => 'admin_sekolah'
        ]);
        
        User::create([
            'name' => 'Admin TK Demo',
            'email' => 'admin@tk.com',
            'password' => Hash::make('password'), 
            'foundation_id' => $foundation->id,
            'school_id' => $schoolTK->id, 
            'role' => 'admin_sekolah'
        ]);
        
        // --- (Data Akun, Kategori, Aturan Biaya biarkan sama) ---
        // 4. Buat Akun (COA)
        $akunKas = Account::create(['foundation_id' => $foundation->id,'code' => '1-100','name' => 'Kas / Bank','type' => 'aktiva','category' => 'Kas']);
        $akunPendapatanSPP = Account::create(['foundation_id' => $foundation->id,'code' => '4-100','name' => 'Pendapatan SPP','type' => 'pendapatan']);
        $akunPendapatanGedung = Account::create(['foundation_id' => $foundation->id,'code' => '4-200','name' => 'Pendapatan Uang Gedung','type' => 'pendapatan']);
        $akunPendapatanEkskul = Account::create(['foundation_id' => $foundation->id,'code' => '4-300','name' => 'Pendapatan Ekskul','type' => 'pendapatan']);

        // 5. Buat Kategori Biaya
        $kategoriSPP = FeeCategory::create(['foundation_id' => $foundation->id,'name' => 'SPP Bulanan','account_id' => $akunPendapatanSPP->id]);
        $kategoriGedung = FeeCategory::create(['foundation_id' => $foundation->id,'name' => 'Uang Gedung 2025','account_id' => $akunPendapatanGedung->id]);
        $kategoriEkskul = FeeCategory::create(['foundation_id' => $foundation->id,'name' => 'Ekskul Renang','account_id' => $akunPendapatanEkskul->id]);

        // 6. Buat Aturan Biaya (Fee Structures)
        FeeStructure::create(['foundation_id' => $foundation->id,'school_id' => $schoolSD->id,'fee_category_id' => $kategoriSPP->id,'name' => 'SPP Bulanan SD','amount' => 500000,'billing_cycle' => 'monthly','is_active' => true]);
        FeeStructure::create(['foundation_id' => $foundation->id,'school_id' => $schoolSD->id,'fee_category_id' => $kategoriGedung->id,'name' => 'Uang Gedung SD 2025','amount' => 3000000,'billing_cycle' => 'one_time','is_active' => true]);
        FeeStructure::create(['foundation_id' => $foundation->id,'school_id' => $schoolTK->id,'fee_category_id' => $kategoriSPP->id,'name' => 'SPP Bulanan TK','amount' => 350000,'billing_cycle' => 'monthly','is_active' => true]);
        FeeStructure::create(['foundation_id' => $foundation->id,'school_id' => $schoolSD->id,'fee_category_id' => $kategoriEkskul->id,'name' => 'Ekskul Renang SD','amount' => 150000,'billing_cycle' => 'monthly','is_active' => true]);


        // --- ================================== ---
        // ---        TAMBAHAN SEEDER BARU        ---
        // --- ================================== ---

        // 7. Buat User (Guru & Orang Tua)
        $guruSD = User::create([
            'name' => 'Budi Guru (Wali Kelas)',
            'email' => 'guru@sd.com',
            'password' => Hash::make('password'), 
            'foundation_id' => $foundation->id,
            'school_id' => $schoolSD->id, // Terikat ke SD
            'role' => 'guru'
        ]);

        $ortuSiswaA = User::create([
            'name' => 'Ayah Siswa A (SD)',
            'email' => 'ortu@sd.com',
            'password' => Hash::make('password'), 
            'foundation_id' => $foundation->id,
            'school_id' => $schoolSD->id, // Terikat ke SD
            'role' => 'orangtua'
        ]);
        
        $ortuSiswaB = User::create([
            'name' => 'Ibu Siswa B (TK)',
            'email' => 'ortu@tk.com',
            'password' => Hash::make('password'), 
            'foundation_id' => $foundation->id,
            'school_id' => $schoolTK->id, // Terikat ke TK
            'role' => 'orangtua'
        ]);

        // 8. Buat Kelas (SchoolClass)
        $kelasSD1A = SchoolClass::create([
            'foundation_id' => $foundation->id,
            'school_id' => $schoolSD->id,
            'name' => 'Kelas 1A - SD',
            'homeroom_teacher_id' => $guruSD->id // <-- Relasi ke Guru
        ]);

        $kelasTKA = SchoolClass::create([
            'foundation_id' => $foundation->id,
            'school_id' => $schoolTK->id,
            'name' => 'Kelas A - TK',
            'homeroom_teacher_id' => null // Belum ada wali kelas
        ]);

        // 9. Buat Siswa (Student)
        Student::create([
            'foundation_id' => $foundation->id,
            'school_id' => $schoolSD->id,
            'class_id' => $kelasSD1A->id, // <-- Relasi ke Kelas 1A
            'parent_id' => $ortuSiswaA->id, // <-- Relasi ke Ortu
            'nis' => 'SD-001',
            'name' => 'Siswa A (Anak Ayah A)',
            'status' => 'active'
        ]);

        Student::create([
            'foundation_id' => $foundation->id,
            'school_id' => $schoolTK->id,
            'class_id' => $kelasTKA->id, // <-- Relasi ke Kelas A
            'parent_id' => $ortuSiswaB->id, // <-- Relasi ke Ortu
            'nis' => 'TK-001',
            'name' => 'Siswa B (Anak Ibu B)',
            'status' => 'active'
        ]);

        Student::create([
            'foundation_id' => $foundation->id,
            'school_id' => $schoolSD->id,
            'class_id' => $kelasSD1A->id, // <-- Relasi ke Kelas 1A
            'parent_id' => null, // Belum ada ortu
            'nis' => 'SD-002',
            'name' => 'Siswa C (Belum ada ortu)',
            'status' => 'active'
        ]);
    }
}