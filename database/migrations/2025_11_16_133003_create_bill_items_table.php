<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     * Tabel ini akan berisi rincian (line items) dari sebuah tagihan.
     */
    public function up(): void
    {
        Schema::create('bill_items', function (Blueprint $table) {
            $table->id();

            // Relasi ke Tagihan Induk
            $table->foreignId('bill_id')
                  ->constrained('bills')
                  ->cascadeOnDelete(); // Jika Bill dihapus, itemnya ikut terhapus

            // Relasi ke Aturan Biaya (untuk melacak ini SPP, Makan, dll)
            $table->foreignId('fee_structure_id')
                  ->nullable()
                  ->constrained('fee_structures')
                  ->nullOnDelete();

            // Relasi ke Kategori Biaya (untuk Jurnal Akrual)
            $table->foreignId('fee_category_id')
                  ->nullable()
                  ->constrained('fee_categories')
                  ->nullOnDelete();

            $table->string('description'); // Deskripsi (misal: "SPP Bulan November")
            $table->decimal('amount', 15, 2); // Jumlah untuk item ini
            
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('bill_items');
    }
};