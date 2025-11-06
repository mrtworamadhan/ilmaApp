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
        Schema::create('teacher_attendances', function (Blueprint $table) {
            $table->id();

            // === KOLOM RELASI ===
            $table->foreignId('foundation_id')->constrained('foundations')->cascadeOnDelete();
            $table->foreignId('school_id')->constrained('schools')->cascadeOnDelete();
            $table->foreignId('teacher_id')->constrained('teachers')->cascadeOnDelete(); // Guru yang diabsen
            
            // User (Staf HR/Piket) yang menginput manual
            $table->foreignId('reported_by_user_id')->nullable()->constrained('users')->nullOnDelete(); 

            // === KOLOM DATA ===
            $table->date('date'); // Tanggal absensi
            $table->enum('status', ['H', 'S', 'I', 'A', 'DL']); // Hadir, Sakit, Izin, Alpa, Dinas Luar
            
            // Fondasi untuk RFID/Fingerprint
            $table->time('timestamp_in')->nullable(); // Jam Masuk
            $table->time('timestamp_out')->nullable(); // Jam Pulang
            
            $table->string('method')->default('manual'); // 'manual', 'rfid', 'fingerprint'
            $table->text('notes')->nullable(); // Catatan (misal: "Sakit demam")

            $table->timestamps();

            // Kunci unik: 1 guru hanya bisa punya 1 record absensi per hari
            $table->unique(['teacher_id', 'date']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('teacher_attendances');
    }
};