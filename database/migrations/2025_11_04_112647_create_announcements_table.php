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
        Schema::create('announcements', function (Blueprint $table) {
            $table->id();

            // Kolom Relasi
            $table->foreignId('foundation_id')->constrained('foundations')->cascadeOnDelete();
            // Pengumuman bisa dibuat di level Yayasan (school_id = null)
            // atau di level Sekolah (school_id = 1)
            $table->foreignId('school_id')->nullable()->constrained('schools')->cascadeOnDelete(); 
            // Siapa user (Admin) yang membuat pengumuman
            $table->foreignId('user_id')->nullable()->constrained('users')->nullOnDelete(); 

            // Kolom Konten
            $table->string('title');
            $table->longText('content')->nullable();
            
            // Kolom Target & Status
            // Untuk masa depan: menyimpan target (misal: ['Wali Murid', 'Guru'])
            $table->json('target_roles')->nullable(); 
            $table->string('status')->default('draft'); // 'draft' atau 'published'
            $table->timestamp('published_at')->nullable(); // Kapan dipublish

            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('announcements');
    }
};