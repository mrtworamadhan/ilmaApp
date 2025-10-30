<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // GANTI 'school_classes' menjadi 'classes'
        Schema::create('classes', function (Blueprint $table) { 
            $table->id();

            // (Sisanya sama persis seperti kode migrasi sebelumnya)
            $table->foreignId('foundation_id')
                  ->constrained('foundations')
                  ->cascadeOnDelete();

            $table->foreignId('school_id')
                  ->constrained('schools')
                  ->cascadeOnDelete();

            $table->string('name'); 

            $table->foreignId('homeroom_teacher_id')
                  ->nullable()
                  ->constrained('users') 
                  ->nullOnDelete();

            $table->timestamps();
        });
    }

    public function down(): void
    {
        // GANTI 'school_classes' menjadi 'classes'
        Schema::dropIfExists('classes');
    }
};
