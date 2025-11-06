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
        Schema::create('student_records', function (Blueprint $table) {
            $table->id();
            
            // === KOLOM RELASI ===
            $table->foreignId('foundation_id')->constrained('foundations')->cascadeOnDelete();
            $table->foreignId('school_id')->constrained('schools')->cascadeOnDelete();
            $table->foreignId('student_id')->constrained('students')->cascadeOnDelete(); // Siswa yang dicatat
            
            // User (Wali Kelas/Kesiswaan) yang melapor
            $table->foreignId('reported_by_user_id')->nullable()->constrained('users')->nullOnDelete(); 

            // === KOLOM DATA ===
            $table->date('date'); // Tanggal kejadian
            $table->string('type'); // 'pelanggaran', 'prestasi', 'perizinan', 'catatan_bk'
            $table->text('description'); // Deskripsi lengkap kejadian
            
            // Poin (positif untuk prestasi, negatif untuk pelanggaran)
            $table->integer('points')->nullable()->default(0); 

            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('student_records');
    }
};