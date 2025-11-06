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
        Schema::create('student_attendances', function (Blueprint $table) {
            $table->id();

            // === KOLOM RELASI ===
            $table->foreignId('foundation_id')->constrained('foundations')->cascadeOnDelete();
            $table->foreignId('school_id')->constrained('schools')->cascadeOnDelete();
            $table->foreignId('class_id')->constrained('classes')->cascadeOnDelete(); // Kelas saat diabsen
            $table->foreignId('student_id')->constrained('students')->cascadeOnDelete(); // Siswa yg diabsen
            
            // User (Guru/Wali Kelas) yang menginput
            $table->foreignId('reported_by_user_id')->nullable()->constrained('users')->nullOnDelete(); 

            // === KOLOM DATA ===
            $table->date('date'); // Tanggal absensi
            $table->enum('status', ['H', 'S', 'I', 'A']); // Hadir, Sakit, Izin, Alpa
            $table->text('notes')->nullable(); // Catatan (misal: "Sakit demam")

            $table->timestamps();

            // Kunci unik: 1 siswa hanya bisa punya 1 record absensi per hari
            $table->unique(['student_id', 'date']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('student_attendances');
    }
};