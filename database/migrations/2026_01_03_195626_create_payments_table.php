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
            $table->foreignId('order_id')->constrained('orders')->onDelete('cascade');
            $table->string('payment_method')->default('card'); // card, cash, online, bank_transfer, other
            $table->string('payment_provider')->nullable(); // stripe, paypal, yookassa, sberbank, etc.
            $table->string('status')->default('pending'); // pending, processing, succeeded, failed, refunded, partially_refunded, cancelled
            $table->decimal('amount', 10, 2);
            $table->string('currency', 3)->default('RUB');
            $table->string('transaction_id')->nullable()->unique();
            $table->json('provider_response')->nullable();
            $table->timestamp('paid_at')->nullable();
            $table->decimal('refunded_amount', 10, 2)->default(0);
            $table->timestamp('refunded_at')->nullable();
            $table->text('notes')->nullable();
            $table->timestamps();
            
            // Индексы
            $table->index('order_id');
            $table->index('status');
            $table->index('payment_method');
            $table->index('transaction_id');
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
