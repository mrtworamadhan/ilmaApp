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
        // Perintah untuk MENGUBAH tabel 'journals'
        Schema::table('journals', function (Blueprint $table) {
            
            // Ubah 2 kolom ini agar 'nullable'
            // dan pakai 'change()' untuk konfirmasi perubahan
            
            $table->string('referenceable_type')->nullable()->change();
            
            $table->unsignedBigInteger('referenceable_id')->nullable()->change();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('journals', function (Blueprint $table) {
            // Ini adalah kebalikannya (jika kita mau rollback)
            $table->string('referenceable_type')->nullable(false)->change();
            $table->unsignedBigInteger('referenceable_id')->nullable(false)->change();
        });
    }
};