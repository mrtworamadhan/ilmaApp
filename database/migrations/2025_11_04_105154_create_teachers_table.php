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
        Schema::create('teachers', function (Blueprint $table) {
            $table->id();
            
            // Kolom Relasi (Wajib)
            $table->foreignId('foundation_id')->constrained('foundations')->cascadeOnDelete();
            $table->foreignId('school_id')->constrained('schools')->cascadeOnDelete();
            
            // Relasi ke User (Opsional)
            // Satu guru BISA jadi punya akun login, bisa juga tidak.
            $table->foreignId('user_id')->nullable()->constrained('users')->nullOnDelete();

            // Data Identitas Guru
            $table->string('nip')->nullable()->unique(); // NIP/Nomor Induk Pegawai
            $table->string('full_name');
            $table->string('gender', 1)->nullable(); // L atau P
            $table->string('phone')->nullable();
            $table->text('address')->nullable();
            $table->string('photo_path')->nullable();
            
            // Info Tambahan
            $table->date('birth_date')->nullable();
            $table->string('employment_status')->nullable(); // Status Kepegawaian (PNS, Honorer, GTY, dll)
            $table->string('education_level')->nullable(); // Pendidikan Terakhir

            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('teachers');
    }
};