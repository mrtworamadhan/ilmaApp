<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('students', function (Blueprint $table) {
            $table->id();

            // 1. Wajib untuk Multi-Tenancy
            $table->foreignId('foundation_id')
                  ->constrained('foundations')
                  ->cascadeOnDelete();

            // 2. Wajib untuk Multi-Level (Siswa ini di sekolah mana)
            $table->foreignId('school_id')
                  ->constrained('schools')
                  ->cascadeOnDelete();

            // 3. Wajib untuk grouping (Siswa ini di kelas mana)
            $table->foreignId('class_id')
                  ->constrained('classes') // Arahkan ke tabel 'classes'
                  ->cascadeOnDelete();
            
            // 4. Akun orang tua (dari tabel 'users')
            $table->foreignId('parent_id')
                  ->nullable()
                  ->constrained('users') // Relasi ke tabel 'users'
                  ->nullOnDelete();

            $table->string('nis')->unique()->nullable(); // Nomor Induk Siswa
            $table->string('name'); // Nama Lengkap Siswa
            
            // Info Tambahan (bisa dikembangkan)
            $table->string('gender')->nullable(); // Laki-laki / Perempuan
            $table->date('birth_date')->nullable();
            
            $table->string('va_number')->nullable(); // Virtual Account Xendit

            // Status Siswa
            $table->enum('status', ['active', 'graduated', 'inactive', 'new'])
                  ->default('new');
            
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('students');
    }
};