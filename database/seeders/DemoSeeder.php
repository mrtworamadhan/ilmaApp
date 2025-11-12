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
        // Inisialisasi Faker untuk data Indonesia
        $faker = Faker::create('id_ID');

        // 0. Buat Role yang dibutuhkan
        $this->command->info('Membuat Roles...');
        $roleAdminYayasan = Role::firstOrCreate(['name' => 'Admin Yayasan', 'guard_name' => 'web']);
        $roleAdminSekolah = Role::firstOrCreate(['name' => 'Admin Sekolah', 'guard_name' => 'web']);
        $roleOrtu = Role::firstOrCreate(['name' => 'Orang Tua', 'guard_name' => 'web']);
        Role::firstOrCreate(['name' => 'Kepala Bagian', 'guard_name' => 'web']);
        $roleKasirKantin = Role::findOrCreate('Kasir Kantin');
        
        // 1. Buat 1 Yayasan (Tenant)
        $this->command->info('Membuat Yayasan...');
        $foundation = Foundation::create([
            'name' => 'Yayasan Pesantren ILMA',
            'email' => 'kontak@pesantren-ilma.com',
            'phone' => '08123456789'
        ]);

        // 2. Buat User Admin Yayasan
        $adminYayasan = User::create([
            'foundation_id' => $foundation->id,
            'name' => 'Admin Yayasan ILMA',
            'email' => 'admin@yayasan.com',
            'password' => Hash::make('password'),
        ]);
        $adminYayasan->assignRole($roleAdminYayasan);

        // 3. Buat Chart of Accounts (COA) Lengkap
        $this->command->info('Membuat Chart of Accounts (COA)...');
        // Aset (1000)
        $accKas = Account::create(['foundation_id' => $foundation->id, 'code' => '1101', 'name' => 'Kas', 'type' => 'aktiva']);
        $accBank = Account::create(['foundation_id' => $foundation->id, 'code' => '1102', 'name' => 'Bank BSI', 'type' => 'aktiva']);
        Account::create(['foundation_id' => $foundation->id, 'code' => '1103', 'name' => 'Bank Xendit', 'type' => 'aktiva']);
        $accPiutangSPP = Account::create(['foundation_id' => $foundation->id, 'code' => '1104', 'name' => 'Piutang SPP', 'type' => 'aktiva']);
        Account::create(['foundation_id' => $foundation->id, 'code' => '1201', 'name' => 'Aset Tetap - Gedung', 'type' => 'aktiva']);
        // Kewajiban (2000)
        Account::create(['foundation_id' => $foundation->id, 'code' => '2101', 'name' => 'Utang Usaha', 'type' => 'kewajiban']);
        // Ekuitas (3000)
        Account::create(['foundation_id' => $foundation->id, 'code' => '3101', 'name' => 'Modal', 'type' => 'ekuitas']);
        // Pendapatan (4000)
        $accPendapatanSPP = Account::create(['foundation_id' => $foundation->id, 'code' => '4101', 'name' => 'Pendapatan SPP', 'type' => 'pendapatan']);
        $accPendapatanGedung = Account::create(['foundation_id' => $foundation->id, 'code' => '4102', 'name' => 'Pendapatan Uang Gedung', 'type' => 'pendapatan']);
        $accPendapatanMakan = Account::create(['foundation_id' => $foundation->id, 'code' => '4103', 'name' => 'Pendapatan Uang Makan', 'type' => 'pendapatan']);
        $accPendapatanJemputan = Account::create(['foundation_id' => $foundation->id, 'code' => '4104', 'name' => 'Pendapatan Jemputan', 'type' => 'pendapatan']);
        // Biaya (5000)
        Account::create(['foundation_id' => $foundation->id, 'code' => '5101', 'name' => 'Biaya Gaji & Honor', 'type' => 'beban']);
        Account::create(['foundation_id' => $foundation->id, 'code' => '5102', 'name' => 'Biaya Listrik, Air, Internet', 'type' => 'beban']);
        Account::create(['foundation_id' => $foundation->id, 'code' => '5103', 'name' => 'Biaya Konsumsi', 'type' => 'beban']);
        Account::create(['foundation_id' => $foundation->id, 'code' => '5104', 'name' => 'Biaya Transportasi', 'type' => 'beban']);
        Account::create(['foundation_id' => $foundation->id, 'code' => '5105', 'name' => 'Biaya ATK', 'type' => 'beban']);


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

        // ==========================================================
        // PERBAIKAN DI SINI (LANGKAH 7)
        // ==========================================================
        $this->command->info('Membuat Aturan Biaya (FeeStructure)...');
        // TK (grade_level 0)
        FeeStructure::create(['foundation_id' => $foundation->id, 'school_id' => $schoolTK->id, 'fee_category_id' => $catGedung->id, 'name' => $catGedung->name . ' TK', 'amount' => 1500000, 'grade_level' => 0]);
        FeeStructure::create(['foundation_id' => $foundation->id, 'school_id' => $schoolTK->id, 'fee_category_id' => $catSPP->id, 'name' => $catSPP->name . ' TK', 'amount' => 200000, 'grade_level' => 0]);
        
        // SD (grade_level 1-6)
        FeeStructure::create(['foundation_id' => $foundation->id, 'school_id' => $schoolSD->id, 'fee_category_id' => $catGedung->id, 'name' => $catGedung->name . ' SD (Kelas 1)', 'amount' => 2500000, 'grade_level' => 1]);
        foreach (range(1, 6) as $grade) {
            FeeStructure::create(['foundation_id' => $foundation->id, 'school_id' => $schoolSD->id, 'fee_category_id' => $catSPP->id, 'name' => $catSPP->name . " SD (Kelas $grade)", 'amount' => 350000, 'grade_level' => $grade]);
            FeeStructure::create(['foundation_id' => $foundation->id, 'school_id' => $schoolSD->id, 'fee_category_id' => $catJemputan->id, 'name' => $catJemputan->name . " SD (Kelas $grade)", 'amount' => 150000, 'grade_level' => $grade]);
        }
        
        // Pesantren (grade_level 7-12)
        FeeStructure::create(['foundation_id' => $foundation->id, 'school_id' => $schoolPondok->id, 'fee_category_id' => $catGedung->id, 'name' => $catGedung->name . ' Pesantren (Kelas 7)', 'amount' => 5000000, 'grade_level' => 7]);
        FeeStructure::create(['foundation_id' => $foundation->id, 'school_id' => $schoolPondok->id, 'fee_category_id' => $catGedung->id, 'name' => $catGedung->name . ' Pesantren (Kelas 10)', 'amount' => 5000000, 'grade_level' => 10]);
        foreach (range(7, 12) as $grade) {
            FeeStructure::create(['foundation_id' => $foundation->id, 'school_id' => $schoolPondok->id, 'fee_category_id' => $catSPP->id, 'name' => $catSPP->name . " Pesantren (Kelas $grade)", 'amount' => 450000, 'grade_level' => $grade]);
            FeeStructure::create(['foundation_id' => $foundation->id, 'school_id' => $schoolPondok->id, 'fee_category_id' => $catMakan->id, 'name' => $catMakan->name . " Pesantren (Kelas $grade)", 'amount' => 350000, 'grade_level' => $grade]);
        }
        // ==========================================================
        // AKHIR PERBAIKAN
        // ==========================================================

        // 8. Buat Kelas dan Siswa (TOTAL 30 SISWA)
        $this->command->info('Memulai pembuatan 30 Siswa & 30 Ortu (AKAN MEMICU API XENDIT)...');
        $this->command->warn('Proses ini akan lambat karena menunggu API call Xendit...');

        // A. Siswa TK (5 orang)
        $kelasTKA = SchoolClass::create(['foundation_id' => $foundation->id, 'school_id' => $schoolTK->id, 'name' => 'TK A', 'grade_level' => 0]);
        foreach (range(1, 5) as $i) {
            $this->command->info("Membuat Siswa TK ke-$i...");
            $ortu = User::create([
                'foundation_id' => $foundation->id,
                'name' => $faker->name(),
                'email' => $faker->unique()->safeEmail(),
                'password' => Hash::make('password')
            ]);
            $ortu->assignRole($roleOrtu);

            Student::create(['foundation_id' => $foundation->id, 'school_id' => $schoolTK->id, 'class_id' => $kelasTKA->id, 'parent_id' => $ortu->id, 'nis' => "TK-A-$i", 'name' => $faker->name(str_contains($faker->title(), 'Mr.') ? 'male' : 'female'), 'status' => 'active']);
        }

        // B. Siswa SD (10 orang)
        $kelasSD1 = SchoolClass::create(['foundation_id' => $foundation->id, 'school_id' => $schoolSD->id, 'name' => "Kelas 1 SD", 'grade_level' => 1]);
        $kelasSD2 = SchoolClass::create(['foundation_id' => $foundation->id, 'school_id' => $schoolSD->id, 'name' => "Kelas 2 SD", 'grade_level' => 2]);
        
        // 5 siswa di Kelas 1
        foreach (range(1, 5) as $i) {
            $this->command->info("Membuat Siswa SD ke-$i (Kelas 1)...");
            $ortu = User::create(['foundation_id' => $foundation->id, 'name' => $faker->name(), 'email' => $faker->unique()->safeEmail(), 'password' => Hash::make('password')]);
            $ortu->assignRole($roleOrtu);
            $student = Student::create(['foundation_id' => $foundation->id, 'school_id' => $schoolSD->id, 'class_id' => $kelasSD1->id, 'parent_id' => $ortu->id, 'nis' => "SD-1-$i", 'name' => $faker->name(str_contains($faker->title(), 'Mr.') ? 'male' : 'female'), 'status' => 'active']);
            if ($i <= 3) {
                $student->optionalFees()->attach($catJemputan->id);
            }
        }
        // 5 siswa di Kelas 2
        foreach (range(6, 10) as $i) {
            $this->command->info("Membuat Siswa SD ke-$i (Kelas 2)...");
            $ortu = User::create(['foundation_id' => $foundation->id, 'name' => $faker->name(), 'email' => $faker->unique()->safeEmail(), 'password' => Hash::make('password')]);
            $ortu->assignRole($roleOrtu);
            $student = Student::create(['foundation_id' => $foundation->id, 'school_id' => $schoolSD->id, 'class_id' => $kelasSD2->id, 'parent_id' => $ortu->id, 'nis' => "SD-2-$i", 'name' => $faker->name(str_contains($faker->title(), 'Mr.') ? 'male' : 'female'), 'status' => 'active']);
        }

        // C. Siswa Pesantren (15 orang)
        $kelasP7 = SchoolClass::create(['foundation_id' => $foundation->id, 'school_id' => $schoolPondok->id, 'name' => "Kelas 7 (MTs)", 'grade_level' => 7]);
        $kelasP8 = SchoolClass::create(['foundation_id' => $foundation->id, 'school_id' => $schoolPondok->id, 'name' => "Kelas 8 (MTs)", 'grade_level' => 8]);
        $kelasP10 = SchoolClass::create(['foundation_id' => $foundation->id, 'school_id' => $schoolPondok->id, 'name' => "Kelas 10 (MA)", 'grade_level' => 10]);

        // 5 santri di Kelas 7
        foreach (range(1, 5) as $i) {
            $this->command->info("Membuat Santri ke-$i (Kelas 7)...");
            $ortu = User::create(['foundation_id' => $foundation->id, 'name' => $faker->name(), 'email' => $faker->unique()->safeEmail(), 'password' => Hash::make('password')]);
            $ortu->assignRole($roleOrtu);
            Student::create(['foundation_id' => $foundation->id, 'school_id' => $schoolPondok->id, 'class_id' => $kelasP7->id, 'parent_id' => $ortu->id, 'nis' => "P-7-$i", 'name' => $faker->name(str_contains($faker->title(), 'Mr.') ? 'male' : 'female'), 'status' => 'active']);
        }
        // 5 santri di Kelas 8
        foreach (range(6, 10) as $i) {
            $this->command->info("Membuat Santri ke-$i (Kelas 8)...");
            $ortu = User::create(['foundation_id' => $foundation->id, 'name' => $faker->name(), 'email' => $faker->unique()->safeEmail(), 'password' => Hash::make('password')]);
            $ortu->assignRole($roleOrtu);
            Student::create(['foundation_id' => $foundation->id, 'school_id' => $schoolPondok->id, 'class_id' => $kelasP8->id, 'parent_id' => $ortu->id, 'nis' => "P-8-$i", 'name' => $faker->name(str_contains($faker->title(), 'Mr.') ? 'male' : 'female'), 'status' => 'active']);
        }
        // 5 santri di Kelas 10
        foreach (range(11, 15) as $i) {
            $this->command->info("Membuat Santri ke-$i (Kelas 10)...");
            $ortu = User::create(['foundation_id' => $foundation->id, 'name' => $faker->name(), 'email' => $faker->unique()->safeEmail(), 'password' => Hash::make('password')]);
            $ortu->assignRole($roleOrtu);
            Student::create(['foundation_id' => $foundation->id, 'school_id' => $schoolPondok->id, 'class_id' => $kelasP10->id, 'parent_id' => $ortu->id, 'nis' => "P-10-$i", 'name' => $faker->name(str_contains($faker->title(), 'Mr.') ? 'male' : 'female'), 'status' => 'active']);
        }


        $this->command->info('===============================================================');
        $this->command->info('DemoSeeder selesai. 1 Yayasan, 3 Sekolah, 30 Siswa, dan 30 Ortu telah dibuat.');
        $this->command->info('30 API call ke Xendit (Test Mode) seharusnya sudah dieksekusi.');
    }
}