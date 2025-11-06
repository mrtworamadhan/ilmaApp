<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::create('admission_registrations', function (Blueprint $table) {
            $table->id();

            // === KOLOM RELASI & STATUS ===
            $table->foreignId('foundation_id')->constrained('foundations')->cascadeOnDelete();
            $table->foreignId('school_id')->constrained('schools')->cascadeOnDelete();
            
            // Kolom status untuk melacak proses PPDB
            $table->string('status')->default('baru'); // 'baru', 'diverifikasi', 'seleksi', 'diterima', 'ditolak', 'menjadi_siswa'
            $table->string('registration_wave')->nullable(); // Gelombang Pendaftaran (misal: "Gelombang 1 2025")
            $table->string('registration_number')->unique()->nullable(); // Nomor Pendaftaran
            
            // === DATA CALON SISWA ===
            $table->string('full_name'); // Nama Lengkap Calon Siswa
            $table->string('gender', 1)->nullable(); // L atau P
            $table->string('birth_place')->nullable();
            $table->date('birth_date')->nullable();
            $table->string('religion')->nullable();
            $table->string('previous_school')->nullable(); // Asal Sekolah

            // === DATA ORANG TUA / WALI (KONTAK UTAMA) ===
            $table->string('parent_name'); // Nama Ayah / Ibu / Wali
            $table->string('parent_phone')->nullable();
            $table->string('parent_email')->nullable();
            
            // === BERKAS (OPSIONAL) ===
            $table->string('payment_proof_path')->nullable(); // Bukti Bayar Formulir
            
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('admission_registrations');
    }
};