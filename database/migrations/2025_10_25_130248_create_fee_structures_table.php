<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('fee_structures', function (Blueprint $table) {
            $table->id();

            // Wajib untuk multi-tenancy
            $table->foreignId('foundation_id')
                  ->constrained('foundations')
                  ->cascadeOnDelete();

            // Aturan ini berlaku untuk sekolah mana?
            $table->foreignId('school_id')
                  ->constrained('schools')
                  ->cascadeOnDelete();

            // Aturan ini untuk kategori biaya apa?
            $table->foreignId('fee_category_id')
                  ->constrained('fee_categories')
                  ->cascadeOnDelete();

            // Nama aturan (misal: "SPP SD Kelas 1 Tahun 2025")
            $table->string('name'); 
            
            $table->decimal('amount', 15, 2)->default(0); // Nominal biayanya
            
            // Kapan ditagih?
            $table->enum('billing_cycle', ['monthly', 'yearly', 'one_time'])
                  ->default('monthly');
            
            $table->boolean('is_active')->default(true); // Untuk on/off aturan
            
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('fee_structures');
    }
};