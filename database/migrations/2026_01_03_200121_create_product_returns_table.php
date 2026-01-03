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
        Schema::create('product_returns', function (Blueprint $table) {
            $table->id();
            $table->foreignId('order_id')->constrained('orders')->onDelete('cascade');
            $table->string('return_number')->unique(); // RET-YYYYMMDD-XXX
            $table->string('status')->default('pending'); // pending, approved, rejected, in_transit, received, refunded, cancelled
            $table->text('reason');
            $table->string('reason_type')->default('other'); // defect, wrong_item, not_as_described, changed_mind, other
            $table->json('items'); // массив товаров для возврата
            $table->decimal('total_amount', 10, 2);
            $table->string('refund_method')->nullable(); // original, store_credit, exchange
            $table->string('refund_status')->nullable(); // pending, processing, completed, failed
            $table->timestamp('refunded_at')->nullable();
            $table->text('notes')->nullable();
            $table->text('customer_notes')->nullable();
            $table->foreignId('processed_by')->nullable()->constrained('users')->onDelete('set null');
            $table->timestamp('processed_at')->nullable();
            $table->timestamps();
            
            // Индексы
            $table->index('order_id');
            $table->index('status');
            $table->index('return_number');
            $table->index('processed_by');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('product_returns');
    }
};
