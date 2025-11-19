<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     * Mengubah tabel 'bills' menjadi "Header Tagihan".
     */
    public function up(): void
    {
        Schema::table('bills', function (Blueprint $table) {

            $table->decimal('total_amount', 15, 2)->after('student_id');

            $table->dropForeign(['fee_category_id']); 
            
            $table->dropColumn('fee_category_id');
            $table->dropColumn('amount');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('bills', function (Blueprint $table) {
            // Kembalikan kolom jika di-rollback
            $table->foreignId('fee_category_id')
                  ->nullable()
                  ->after('student_id')
                  ->constrained('fee_categories')
                  ->nullOnDelete();
            
            $table->decimal('amount', 15, 2)->after('fee_category_id');

            // Hapus kolom total_amount
            $table->dropColumn('total_amount');
        });
    }
};