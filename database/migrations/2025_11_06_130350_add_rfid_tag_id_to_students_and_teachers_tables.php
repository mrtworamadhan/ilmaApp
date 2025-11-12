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
        Schema::table('students', function (Blueprint $table) {
            $table->string('rfid_tag_id')->nullable()->unique()->after('status');
        });

        Schema::table('teachers', function (Blueprint $table) {
            $table->string('rfid_tag_id')->nullable()->unique()->after('nip');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('students', function (Blueprint $table) {
            $table->dropColumn('rfid_tag_id');
        });

        Schema::table('teachers', function (Blueprint $table) {
            $table->dropColumn('rfid_tag_id');
        });
    }
};