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
        Schema::create('admission_batches', function (Blueprint $table) {
            $table->id();
            
            // Kolom Relasi (Wajib)
            $table->foreignId('foundation_id')->constrained('foundations')->cascadeOnDelete();
            $table->foreignId('school_id')->constrained('schools')->cascadeOnDelete();

            // Kolom Pengaturan Gelombang
            $table->string('name'); // Nama Gelombang (misal: "Gelombang 1 2025/2026")
            $table->text('description')->nullable(); // Deskripsi
            $table->date('start_date'); // Tanggal Mulai Pendaftaran
            $table->date('end_date'); // Tanggal Selesai Pendaftaran
            
            // Biaya Formulir (jika ada)
            $table->decimal('fee_amount', 15, 2)->nullable()->default(0); 
            
            // Status Gelombang
            $table->boolean('is_active')->default(false); // Apakah gelombang ini sedang dibuka?

            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('admission_batches');
    }
};