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
        Schema::create('vendor_disbursements', function (Blueprint $table) {
            $table->id();
            $table->foreignId('foundation_id')->constrained('foundations')->cascadeOnDelete();
            $table->foreignId('school_id')->constrained('schools')->cascadeOnDelete();
            
            // Vendor mana yang mengajukan
            $table->foreignId('vendor_id')->constrained('vendors')->cascadeOnDelete(); 
            
            $table->decimal('amount', 15, 2); // Jumlah yang diminta
            $table->text('notes')->nullable(); // Catatan dari vendor
            
            // Status pengajuan
            $table->string('status')->default('requested'); // requested, approved, paid, rejected
            
            // Diisi oleh Staf Keuangan saat memproses
            $table->timestamp('processed_at')->nullable(); 
            $table->foreignId('processed_by')->nullable()->constrained('users')->nullOnDelete(); // Staf Keuangan
            
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('vendor_disbursements');
    }
};