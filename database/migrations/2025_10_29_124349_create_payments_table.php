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
        Schema::create('payments', function (Blueprint $table) {
            $table->id();
            $table->foreignId('invoice_id')->constrained();
            $table->foreignId('user_id')->constrained(); // who recorded the payment
            $table->decimal('amount', 10, 2);
            $table->string('payment_method'); // cash, bank_transfer, mobile_money
            $table->string('reference_number')->nullable();
            $table->string('status')->default('pending'); // pending, completed, failed
            $table->text('notes')->nullable();
            $table->timestamp('payment_date');
            $table->timestamps();
            $table->softDeletes();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('payments');
    }
};
