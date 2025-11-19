<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     * * Modifikasi untuk ISAK 35 & system_code:
     * 1. Ubah enum 'type' sesuai standar ISAK 35 (Aset, Liabilitas, Aset Neto, Pendapatan, Beban).
     * 2. Tambah 'normal_balance' (Debit/Kredit) dari CSV.
     * 3. 'category' akan diisi dengan Kategori Laporan Keuangan (Aset Lancar, dll).
     * 4. Tambah 'system_code' untuk pengait logika aplikasi.
     * 5. Tambah index unik untuk 'system_code' per 'foundation_id'.
     */
    public function up(): void
    {
        Schema::create('accounts', function (Blueprint $table) {
            $table->id();
            
            // Wajib untuk multi-tenancy
            $table->foreignId('foundation_id')
                  ->constrained('foundations')
                  ->cascadeOnDelete();

            $table->string('code')->nullable(); // Kode Akun (misal: 1111)
            $table->string('name'); // Nama Akun (misal: Kas)
            
            // Tipe Akun (Kelompok Utama Laporan) - Sesuai CSV "Kelompok"
            $table->enum('type', ['Aset', 'Liabilitas', 'Aset Neto', 'Pendapatan', 'Beban']);
            
            // Saldo Normal Akun - Sesuai CSV "Tipe Akun"
            $table->enum('normal_balance', ['Debit', 'Kredit']);
            
            // Kategori Laporan Keuangan - Sesuai CSV "Kategori Laporan Keuangan"
            $table->string('category')->nullable(); // (misal: Aset Lancar, Kewajiban Jangka Pendek)

            // Pengait untuk logika aplikasi (HARUS UNIK per yayasan)
            $table->string('system_code')->nullable();
            
            // Untuk hierarki (opsional tapi bagus)
            $table->foreignId('parent_id')->nullable()->constrained('accounts')->nullOnDelete();
            
            $table->timestamps();

            // Index agar system_code unik per yayasan
            // Ini mencegah duplikasi 'kas_operasional_default' di yayasan yang sama
            $table->unique(['foundation_id', 'system_code']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('accounts');
    }
};