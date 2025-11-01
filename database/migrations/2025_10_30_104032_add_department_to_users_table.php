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
        Schema::table('users', function (Blueprint $table) {
            // Kolom ini nullable, karena Admin Yayasan/Sekolah tidak punya departemen
            $table->foreignId('department_id')
                ->nullable()
                ->after('school_id')
                ->constrained('departments')
                ->nullOnDelete(); // Jika departemen dihapus, user-nya jadi null
        });
    }

    public function down(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->dropForeign(['department_id']);
            $table->dropColumn('department_id');
        });
    }
};
