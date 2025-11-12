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
        Schema::table('schools', function (Blueprint $table) {
            // Kita tambahkan 2 kolom baru setelah 'status'
            $table->time('teacher_check_in_time')->nullable()->default('07:00:00')->after('headmaster');
            $table->time('teacher_check_out_time')->nullable()->default('14:00:00')->after('teacher_check_in_time');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('schools', function (Blueprint $table) {
            $table->dropColumn('teacher_check_in_time');
            $table->dropColumn('teacher_check_out_time');
        });
    }
};