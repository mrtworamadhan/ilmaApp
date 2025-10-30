<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('bills', function (Blueprint $table) {
            $table->id();

            // 1. Wajib untuk Multi-Tenancy
            $table->foreignId('foundation_id')
                  ->constrained('foundations')
                  ->cascadeOnDelete();

            // 2. Wajib untuk Multi-Level (Tagihan ini milik sekolah mana)
            // Meskipun 'student' sudah punya, ini akan SANGAT mempermudah query
            $table->foreignId('school_id')
                  ->constrained('schools')
                  ->cascadeOnDelete();

            // 3. Siswa yang ditagih 
            $table->foreignId('student_id')
                  ->constrained('students') 
                  ->cascadeOnDelete();

            // 4. Kategori tagihan (SPP, Gedung, dll)
            // Ini adalah relasi ke 'fee_categories' yang kita buat
            $table->foreignId('fee_category_id')
                  ->constrained('fee_categories')
                  ->cascadeOnDelete();

            // 5. Detail Tagihan 
            $table->decimal('amount', 15, 2); // Jumlah tagihan
            $table->date('due_date'); // Tanggal jatuh tempo
            
            // Bulan tagihan (khusus untuk tagihan bulanan)
            $table->string('month')->nullable()->comment('Format: YYYY-MM'); 

            // Status tagihan 
            $table->enum('status', ['unpaid', 'paid', 'overdue', 'cancelled'])
                  ->default('unpaid');

            $table->text('description')->nullable(); // Keterangan tambahan
            
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('bills');
    }
};