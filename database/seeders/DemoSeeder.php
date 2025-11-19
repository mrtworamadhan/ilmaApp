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
use Spatie\Permission\Models\Role;
use Faker\Factory as Faker;

class DemoSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
         \Illuminate\Support\Facades\Event::fake();
    
        // Pastikan model Foundation booted
        Foundation::flushEventListeners();
        Foundation::boot();
        // Inisialisasi Faker untuk data Indonesia
        $faker = Faker::create('id_ID');

        // 0. Buat Role yang dibutuhkan
        $this->command->info('Membuat Roles...');
        $roleAdminYayasan = Role::firstOrCreate(['name' => 'Admin Yayasan', 'guard_name' => 'web']);
        $roleAdminSekolah = Role::firstOrCreate(['name' => 'Admin Sekolah', 'guard_name' => 'web']);
        // $roleOrtu = Role::firstOrCreate(['name' => 'Orang Tua', 'guard_name' => 'web']); // <-- DIHAPUS
        $roleMurid = Role::firstOrCreate(['name' => 'Murid', 'guard_name' => 'web']); // <-- DITAMBAHKAN
        Role::firstOrCreate(['name' => 'Kepala Bagian', 'guard_name' => 'web']);
        Role::firstOrCreate(['name' => 'Wali Kelas', 'guard_name' => 'web']);
        $roleKasirKantin = Role::findOrCreate('Kasir Kantin');
        $roleGuru = Role::firstOrCreate(['name' => 'Guru', 'guard_name' => 'web']);

        // 1. Buat 1 Yayasan (Tenant)
        // INI AKAN OTOMATIS MEMICU FoundationObserver
        $this->command->info('Membuat Yayasan (Memicu FoundationObserver)...');
        $foundation = Foundation::create([
            'name' => 'Yayasan Pesantren ILMA',
            'email' => 'admin@pesantren.com',
            'phone' => '08123456789',
            'address' => 'Jl. Tes 12344',
            'enabled_modules' => ["finance","payroll","savings","cashless","ppdb","attendance","student_records","lms","announcement"]
        ]);
        $foundationObserver = app(\App\Observers\FoundationObserver::class);
        $foundationObserver->created($foundation);

        // 2. Buat User Admin Yayasan
        $adminYayasan = User::create([
            'foundation_id' => $foundation->id,
            'name' => 'Admin Yayasan ILMA',
            'email' => 'admin@yayasan.com',
            'password' => Hash::make('password'),
        ]);
        $adminYayasan->assignRole($roleAdminYayasan);

        // 3. Persiapan Akun Pendapatan untuk Demo FeeCategory
        // -----------------------------------------------------------------
        // Mensimulasikan Admin Yayasan membuat Induk Akun (Parent)
        // dan memetakan semua pendapatan ke induk tersebut.
        // -----------------------------------------------------------------
        $this->command->info('Mencari/Membuat Akun Pendapatan Demo (dengan Induk Akun)...');

        // A. BUAT INDUK AKUN (PARENT ACCOUNT)
        $accIndukPendapatan = Account::create([
            'foundation_id'   => $foundation->id,
            'code'            => '4100', // Kode Induk
            'name'            => 'Pendapatan Pendidikan (Siswa)',
            'normal_balance'  => 'Kredit',
            'category'        => 'Laporan Penghasilan Komprehensif',
            'type'            => 'Pendapatan',
            'system_code'     => null, 
            'parent_id'       => null, // Dia adalah induk
        ]);

        // B. CARI Akun SPP (dibuat Observer) dan UPDATE parent_id nya
        $accPendapatanSPP = Account::where('foundation_id', $foundation->id)
                                  ->where('system_code', 'pendapatan_spp_default')
                                  ->firstOrFail();
        // Update nama agar lebih spesifik & set parent
        $accPendapatanSPP->update([
            'name'      => 'Pendapatan SPP (Sumbangan)',
            'parent_id' => $accIndukPendapatan->id
        ]);

        // C. BUAT Akun Uang Gedung (sebagai anak dari Induk)
        $accPendapatanGedung = Account::create([
            'foundation_id'   => $foundation->id,
            'code'            => '4102', 
            'name'            => 'Pendapatan Uang Gedung',
            'normal_balance'  => 'Kredit',
            'category'        => 'Laporan Penghasilan Komprehensif',
            'type'            => 'Pendapatan',
            'system_code'     => null, 
            'parent_id'       => $accIndukPendapatan->id, // <-- Set Parent
        ]);

        // D. BUAT Akun Uang Makan (sebagai anak dari Induk)
        $accPendapatanMakan = Account::create([
            'foundation_id'   => $foundation->id,
            'code'            => '4103', 
            'name'            => 'Pendapatan Uang Makan',
            'normal_balance'  => 'Kredit',
            'category'        => 'Laporan Penghasilan Komprehensif',
            'type'            => 'Pendapatan',
            'system_code'     => null,
            'parent_id'       => $accIndukPendapatan->id, // <-- Set Parent
        ]);

        // E. BUAT Akun Uang Jemputan (sebagai anak dari Induk)
        $accPendapatanJemputan = Account::create([
            'foundation_id'   => $foundation->id,
            'code'            => '4104', 
            'name'            => 'Pendapatan Jemputan',
            'normal_balance'  => 'Kredit',
            'category'        => 'Laporan Penghasilan Komprehensif',
            'type'            => 'Pendapatan',
            'system_code'     => null,
            'parent_id'       => $accIndukPendapatan->id, // <-- Set Parent
        ]);

        // 4. Buat 3 Sekolah (TK, SD, Pesantren)
        $this->command->info('Membuat 3 Sekolah...');
        $schoolTK = School::create(['foundation_id' => $foundation->id, 'name' => 'TK ILMA', 'level' => 'tk', 'headmaster' => 'Ibu Ani']);
        $schoolSD = School::create(['foundation_id' => $foundation->id, 'name' => 'SD ILMA', 'level' => 'sd', 'headmaster' => 'Bpk. Budi']);
        $schoolPondok = School::create(['foundation_id' => $foundation->id, 'name' => 'Pesantren ILMA', 'level' => 'pondok', 'headmaster' => 'Kyai Haji Fulan']);

        // 5. Buat User Admin Sekolah
        $adminTK = User::create(['foundation_id' => $foundation->id, 'school_id' => $schoolTK->id, 'name' => 'Admin TK', 'email' => 'admin@tk.com', 'password' => Hash::make('password')]);
        $adminTK->assignRole($roleAdminSekolah);
        $adminSD = User::create(['foundation_id' => $foundation->id, 'school_id' => $schoolSD->id, 'name' => 'Admin SD', 'email' => 'admin@sd.com', 'password' => Hash::make('password')]);
        $adminSD->assignRole($roleAdminSekolah);
        $adminPondok = User::create(['foundation_id' => $foundation->id, 'school_id' => $schoolPondok->id, 'name' => 'Admin Pesantren', 'email' => 'admin@pondok.com', 'password' => Hash::make('password')]);
        $adminPondok->assignRole($roleAdminSekolah);

        // 6. Buat Kategori Biaya (FeeCategory)
        $this->command->info('Membuat Kategori Biaya...');
        $catGedung = FeeCategory::create(['foundation_id' => $foundation->id, 'account_id' => $accPendapatanGedung->id, 'name' => 'Uang Gedung']);
        $catSPP = FeeCategory::create(['foundation_id' => $foundation->id, 'account_id' => $accPendapatanSPP->id, 'name' => 'SPP']);
        $catMakan = FeeCategory::create(['foundation_id' => $foundation->id, 'account_id' => $accPendapatanMakan->id, 'name' => 'Uang Makan']);
        $catJemputan = FeeCategory::create(['foundation_id' => $foundation->id, 'account_id' => $accPendapatanJemputan->id, 'name' => 'Jemputan', 'is_optional' => true]);

        // 7. Buat Aturan Biaya (FeeStructure)
        $this->command->info('Membuat Aturan Biaya (FeeStructure)...');
        FeeStructure::create(['foundation_id' => $foundation->id, 'school_id' => $schoolTK->id, 'fee_category_id' => $catGedung->id, 'name' => $catGedung->name . ' TK', 'amount' => 1500000, 'grade_level' => 0]);
        FeeStructure::create(['foundation_id' => $foundation->id, 'school_id' => $schoolTK->id, 'fee_category_id' => $catSPP->id, 'name' => $catSPP->name . ' TK', 'amount' => 200000, 'grade_level' => 0]);
        FeeStructure::create(['foundation_id' => $foundation->id, 'school_id' => $schoolSD->id, 'fee_category_id' => $catGedung->id, 'name' => $catGedung->name . ' SD (Kelas 1)', 'amount' => 2500000, 'grade_level' => 1]);
        foreach (range(1, 6) as $grade) {
            FeeStructure::create(['foundation_id' => $foundation->id, 'school_id' => $schoolSD->id, 'fee_category_id' => $catSPP->id, 'name' => $catSPP->name . " SD (Kelas $grade)", 'amount' => 350000, 'grade_level' => $grade]);
            FeeStructure::create(['foundation_id' => $foundation->id, 'school_id' => $schoolSD->id, 'fee_category_id' => $catJemputan->id, 'name' => $catJemputan->name . " SD (Kelas $grade)", 'amount' => 150000, 'grade_level' => $grade]);
        }
        FeeStructure::create(['foundation_id' => $foundation->id, 'school_id' => $schoolPondok->id, 'fee_category_id' => $catGedung->id, 'name' => $catGedung->name . ' Pesantren (Kelas 7)', 'amount' => 5000000, 'grade_level' => 7]);
        FeeStructure::create(['foundation_id' => $foundation->id, 'school_id' => $schoolPondok->id, 'fee_category_id' => $catGedung->id, 'name' => $catGedung->name . ' Pesantren (Kelas 10)', 'amount' => 5000000, 'grade_level' => 10]);
        foreach (range(7, 12) as $grade) {
            FeeStructure::create(['foundation_id' => $foundation->id, 'school_id' => $schoolPondok->id, 'fee_category_id' => $catSPP->id, 'name' => $catSPP->name . " Pesantren (Kelas $grade)", 'amount' => 450000, 'grade_level' => $grade]);
            FeeStructure::create(['foundation_id' => $foundation->id, 'school_id' => $schoolPondok->id, 'fee_category_id' => $catMakan->id, 'name' => $catMakan->name . " Pesantren (Kelas $grade)", 'amount' => 350000, 'grade_level' => $grade]);
        }
       
        // 8. Buat Kelas dan Siswa (TOTAL 30 SISWA)
        // LOGIKA DIUBAH: Tidak ada Ortu, langsung buat User untuk Murid
        $this->command->info('Memulai pembuatan 30 Siswa & 30 Akun User Murid...');
        $this->command->warn('Proses ini akan lambat karena menunggu API call Xendit...');

        // A. Siswa TK (5 orang)
        $kelasTKA = SchoolClass::create(['foundation_id' => $foundation->id, 'school_id' => $schoolTK->id, 'name' => 'TK A', 'grade_level' => 0]);
        foreach (range(1, 5) as $i) {
            $this->command->info("Membuat Siswa TK & Akun User Murid ke-$i...");
            
            // Generate nama tanpa title (nama anak-anak)
            $studentName = $this->generateStudentName($faker);
            $rfidTag = $faker->numerify('###########'); // 11 digit random

            // Buat User Akun untuk Murid
            $muridUser = User::create([
                'foundation_id' => $foundation->id,
                'name' => $studentName,
                'email' => $faker->unique()->safeEmail(),
                'password' => Hash::make('password')
            ]);
            $muridUser->assignRole($roleMurid);

            // Buat Data Murid, kaitkan ke User-nya
            Student::create([
                'foundation_id' => $foundation->id, 
                'school_id' => $schoolTK->id, 
                'class_id' => $kelasTKA->id, 
                'parent_id' => $muridUser->id,
                'nis' => "TK-A-$i", 
                'full_name' => $studentName, // ganti 'name' menjadi 'full_name'
                'rfid_tag_id' => $rfidTag, // tambahkan RFID tag
                'status' => 'active'
            ]);
        }

        // B. Siswa SD (10 orang)
        $kelasSD1 = SchoolClass::create(['foundation_id' => $foundation->id, 'school_id' => $schoolSD->id, 'name' => "Kelas 1 SD", 'grade_level' => 1]);
        $kelasSD2 = SchoolClass::create(['foundation_id' => $foundation->id, 'school_id' => $schoolSD->id, 'name' => "Kelas 2 SD", 'grade_level' => 2]);

        // 5 siswa di Kelas 1
        foreach (range(1, 5) as $i) {
            $this->command->info("Membuat Siswa SD ke-$i (Kelas 1)...");
            $studentName = $this->generateStudentName($faker);
            $rfidTag = $faker->numerify('###########');

            $muridUser = User::create([
                'foundation_id' => $foundation->id, 
                'name' => $studentName, 
                'email' => $faker->unique()->safeEmail(), 
                'password' => Hash::make('password')
            ]);
            $muridUser->assignRole($roleMurid);

            $student = Student::create([
                'foundation_id' => $foundation->id, 
                'school_id' => $schoolSD->id, 
                'class_id' => $kelasSD1->id, 
                'parent_id' => $muridUser->id, 
                'nis' => "SD-1-$i", 
                'full_name' => $studentName, // ganti 'name' menjadi 'full_name'
                'rfid_tag_id' => $rfidTag, // tambahkan RFID tag
                'status' => 'active'
            ]);
            
            if ($i <= 3) {
                $student->optionalFees()->attach($catJemputan->id);
            }
        }

        // 5 siswa di Kelas 2
        foreach (range(6, 10) as $i) {
            $this->command->info("Membuat Siswa SD ke-$i (Kelas 2)...");
            $studentName = $this->generateStudentName($faker);
            $rfidTag = $faker->numerify('###########');

            $muridUser = User::create([
                'foundation_id' => $foundation->id, 
                'name' => $studentName, 
                'email' => $faker->unique()->safeEmail(), 
                'password' => Hash::make('password')
            ]);
            $muridUser->assignRole($roleMurid);

            $student = Student::create([
                'foundation_id' => $foundation->id, 
                'school_id' => $schoolSD->id, 
                'class_id' => $kelasSD2->id, 
                'parent_id' => $muridUser->id, 
                'nis' => "SD-2-$i", 
                'full_name' => $studentName, // ganti 'name' menjadi 'full_name'
                'rfid_tag_id' => $rfidTag, // tambahkan RFID tag
                'status' => 'active'
            ]);
        }

        // C. Siswa Pesantren (15 orang)
        $kelasP7 = SchoolClass::create(['foundation_id' => $foundation->id, 'school_id' => $schoolPondok->id, 'name' => "Kelas 7 (MTs)", 'grade_level' => 7]);
        $kelasP8 = SchoolClass::create(['foundation_id' => $foundation->id, 'school_id' => $schoolPondok->id, 'name' => "Kelas 8 (MTs)", 'grade_level' => 8]);
        $kelasP10 = SchoolClass::create(['foundation_id' => $foundation->id, 'school_id' => $schoolPondok->id, 'name' => "Kelas 10 (MA)", 'grade_level' => 10]);

        // 5 santri di Kelas 7
        foreach (range(1, 5) as $i) {
            $this->command->info("Membuat Santri ke-$i (Kelas 7)...");
            $studentName = $this->generateStudentName($faker);
            $rfidTag = $faker->numerify('###########');

            $muridUser = User::create([
                'foundation_id' => $foundation->id, 
                'name' => $studentName, 
                'email' => $faker->unique()->safeEmail(), 
                'password' => Hash::make('password')
            ]);
            $muridUser->assignRole($roleMurid);

            Student::create([
                'foundation_id' => $foundation->id, 
                'school_id' => $schoolPondok->id, 
                'class_id' => $kelasP7->id, 
                'parent_id' => $muridUser->id, 
                'nis' => "P-7-$i", 
                'full_name' => $studentName, // ganti 'name' menjadi 'full_name'
                'rfid_tag_id' => $rfidTag, // tambahkan RFID tag
                'status' => 'active'
            ]);
        }

        // 5 santri di Kelas 8
        foreach (range(6, 10) as $i) {
            $this->command->info("Membuat Santri ke-$i (Kelas 8)...");
            $studentName = $this->generateStudentName($faker);
            $rfidTag = $faker->numerify('###########');

            $muridUser = User::create([
                'foundation_id' => $foundation->id, 
                'name' => $studentName, 
                'email' => $faker->unique()->safeEmail(), 
                'password' => Hash::make('password')
            ]);
            $muridUser->assignRole($roleMurid);

            Student::create([
                'foundation_id' => $foundation->id, 
                'school_id' => $schoolPondok->id, 
                'class_id' => $kelasP8->id, 
                'parent_id' => $muridUser->id, 
                'nis' => "P-8-$i", 
                'full_name' => $studentName, // ganti 'name' menjadi 'full_name'
                'rfid_tag_id' => $rfidTag, // tambahkan RFID tag
                'status' => 'active'
            ]);
        }

        // 5 santri di Kelas 10
        foreach (range(11, 15) as $i) {
            $this->command->info("Membuat Santri ke-$i (Kelas 10)...");
            $studentName = $this->generateStudentName($faker);
            $rfidTag = $faker->numerify('###########');

            $muridUser = User::create([
                'foundation_id' => $foundation->id, 
                'name' => $studentName, 
                'email' => $faker->unique()->safeEmail(), 
                'password' => Hash::make('password')
            ]);
            $muridUser->assignRole($roleMurid);

            Student::create([
                'foundation_id' => $foundation->id, 
                'school_id' => $schoolPondok->id, 
                'class_id' => $kelasP10->id, 
                'parent_id' => $muridUser->id, 
                'nis' => "P-10-$i", 
                'full_name' => $studentName, // ganti 'name' menjadi 'full_name'
                'rfid_tag_id' => $rfidTag, // tambahkan RFID tag
                'status' => 'active'
            ]);
        }

        // 10. BUAT GURU UNTUK SETIAP SEKOLAH (5 guru per sekolah)
        $this->command->info('Membuat 15 Guru (5 guru per sekolah)...');

        $sekolahList = [$schoolTK, $schoolSD, $schoolPondok];
        $jenisKelamin = ['L', 'P'];
        $statusKepegawaian = ['PNS', 'Honorer', 'GTY'];
        $pendidikan = ['S1', 'S2', 'S3'];

        foreach ($sekolahList as $sekolah) {
            for ($i = 1; $i <= 5; $i++) {
                $gender = $faker->randomElement($jenisKelamin);
                $teacherName = $this->generateTeacherName($faker, $gender);
                
                // Buat User untuk Guru
                $guruUser = User::create([
                    'foundation_id' => $foundation->id,
                    'school_id' => $sekolah->id,
                    'name' => $teacherName,
                    'email' => $faker->unique()->safeEmail(),
                    'password' => Hash::make('password')
                ]);
                $guruUser->assignRole($roleGuru);

                // Buat Data Guru
                \App\Models\Teacher::create([
                    'foundation_id' => $foundation->id,
                    'school_id' => $sekolah->id,
                    'user_id' => $guruUser->id,
                    'nip' => $faker->numerify('############'), // 12 digit NIP
                    'full_name' => $teacherName,
                    'gender' => $gender,
                    'phone' => $faker->phoneNumber(),
                    'address' => $faker->address(),
                    'birth_date' => $faker->dateTimeBetween('-50 years', '-25 years')->format('Y-m-d'),
                    'employment_status' => $faker->randomElement($statusKepegawaian),
                    'education_level' => $faker->randomElement($pendidikan),
                ]);

                $this->command->info("Guru {$teacherName} berhasil dibuat untuk {$sekolah->name}");
            }
        }

        $this->command->info('===============================================================');
        $this->command->info('DemoSeeder selesai. 1 Yayasan, 3 Sekolah, 30 Siswa, dan 15 Guru telah dibuat.');
        $this->command->info('COA ISAK 35 & Akun Sistem dibuat oleh FoundationObserver.');
    }
    private function generateStudentName($faker)
    {
        $firstNames = [
            'Ahmad', 'Ali', 'Budi', 'Citra', 'Dewi', 'Eka', 'Fajar', 'Gita', 'Hadi', 'Indra',
            'Joko', 'Kartika', 'Lia', 'Maya', 'Nina', 'Oki', 'Putri', 'Rani', 'Sari', 'Toni',
            'Udin', 'Vina', 'Wawan', 'Yuni', 'Zaki', 'Angga', 'Bayu', 'Candra', 'Dian', 'Eko'
        ];
        
        $lastNames = [
            'Santoso', 'Wijaya', 'Pratama', 'Sari', 'Nugroho', 'Putra', 'Kusuma', 'Hadi', 'Siregar', 'Ginting',
            'Siregar', 'Simanjuntak', 'Sihombing', 'Sitompul', 'Lumban', 'Nainggolan', 'Siahaan', 'Sitorus'
        ];
        
        return $faker->randomElement($firstNames) . ' ' . $faker->randomElement($lastNames);
    }

    /**
     * Generate nama guru dengan gender yang sesuai
     */
    private function generateTeacherName($faker, $gender)
    {
        $maleFirstNames = ['Ahmad', 'Budi', 'Joko', 'Hadi', 'Indra', 'Eko', 'Rudi', 'Slamet', 'Tri', 'Wahyu'];
        $femaleFirstNames = ['Siti', 'Dewi', 'Sri', 'Rini', 'Maya', 'Linda', 'Ani', 'Rita', 'Diana', 'Yuni'];
        
        $lastNames = [
            'Santoso', 'Wijaya', 'Pratama', 'Nugroho', 'Kusuma', 'Hadi', 'Siregar', 
            'Ginting', 'Simanjuntak', 'Sihombing', 'Lumban', 'Nainggolan'
        ];
        
        $firstName = $gender === 'L' 
            ? $faker->randomElement($maleFirstNames)
            : $faker->randomElement($femaleFirstNames);
        
        return $firstName . ' ' . $faker->randomElement($lastNames);
    }
}