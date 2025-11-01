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
        Schema::create('saving_transactions', function (Blueprint $table) {
            $table->id();
            $table->foreignId('foundation_id')->constrained()->cascadeOnDelete();
            $table->foreignId('saving_account_id')->constrained()->cascadeOnDelete();

            $table->enum('type', ['CREDIT', 'DEBIT']); // CREDIT = Setor, DEBIT = Tarik
            $table->decimal('amount', 15, 2);
            $table->string('description');

            // Admin/User yg mencatat transaksi ini (Bendahara)
            $table->foreignId('user_id')->nullable()->constrained('users')->nullOnDelete(); 

            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('saving_transactions');
    }
};
