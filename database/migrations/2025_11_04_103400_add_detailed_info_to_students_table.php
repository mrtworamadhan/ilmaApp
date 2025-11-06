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
            // 1. Ganti nama kolom 'name' menjadi 'full_name'
            $table->renameColumn('name', 'full_name');
            
            // 2. Tambahkan kolom baru setelah 'full_name'
            $table->string('nickname')->nullable()->after('full_name');
            $table->string('nisn')->nullable()->unique()->after('nis');
            
            // 3. Tambahkan kolom detail siswa (setelah 'birth_date')
            $table->string('birth_place')->nullable()->after('birth_date');
            $table->string('religion')->nullable()->after('birth_place');
            $table->string('citizenship')->nullable()->after('religion'); // Kewarganegaraan
            $table->unsignedTinyInteger('child_order')->nullable()->after('citizenship'); // Anak ke-
            $table->unsignedTinyInteger('siblings_count')->nullable()->after('child_order'); // Jml saudara
            
            // 4. Tambahkan kolom kontak & foto (setelah 'siblings_count')
            $table->text('address')->nullable()->after('siblings_count');
            $table->string('phone')->nullable()->after('address');
            $table->string('photo_path')->nullable()->after('phone');

            // 5. Tambahkan kolom data Ayah (setelah 'photo_path')
            $table->string('father_name')->nullable()->after('photo_path');
            $table->string('father_education')->nullable()->after('father_name');
            $table->string('father_job')->nullable()->after('father_education');
            
            // 6. Tambahkan kolom data Ibu (setelah 'father_job')
            $table->string('mother_name')->nullable()->after('father_job');
            $table->string('mother_education')->nullable()->after('mother_name');
            $table->string('mother_job')->nullable()->after('mother_education');

            // 7. Tambahkan kolom data Wali (setelah 'mother_job')
            $table->string('guardian_name')->nullable()->after('mother_job');
            $table->string('guardian_relationship')->nullable()->after('guardian_name');
            $table->text('guardian_address')->nullable()->after('guardian_relationship');
            $table->string('guardian_phone')->nullable()->after('guardian_address');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('students', function (Blueprint $table) {
            // 1. Kembalikan nama kolom
            $table->renameColumn('full_name', 'name');

            // 2. Hapus semua kolom yang kita tambahkan
            $table->dropColumn([
                'nickname',
                'nisn',
                'birth_place',
                'religion',
                'citizenship',
                'child_order',
                'siblings_count',
                'address',
                'phone',
                'photo_path',
                'father_name',
                'father_education',
                'father_job',
                'mother_name',
                'mother_education',
                'mother_job',
                'guardian_name',
                'guardian_relationship',
                'guardian_address',
                'guardian_phone',
            ]);
        });
    }
};