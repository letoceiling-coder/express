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
        Schema::create('orders', function (Blueprint $table) {
            $table->id();
            $table->string('order_id')->unique(); // ORD-20251220-1 format
            $table->unsignedBigInteger('telegram_id'); // ID пользователя Telegram
            $table->string('status')->default('new'); // new, accepted, preparing, ready_for_delivery, in_transit, delivered, cancelled
            $table->string('phone');
            $table->text('delivery_address');
            $table->string('delivery_type')->default('courier'); // courier, pickup, self_delivery
            $table->string('delivery_time'); // "15:00-16:00" or datetime
            $table->date('delivery_date')->nullable();
            $table->time('delivery_time_from')->nullable();
            $table->time('delivery_time_to')->nullable();
            $table->decimal('delivery_cost', 10, 2)->default(0);
            $table->text('comment')->nullable(); // комментарий клиента
            $table->text('notes')->nullable(); // внутренние заметки
            $table->decimal('total_amount', 10, 2);
            $table->string('payment_id')->nullable();
            $table->string('payment_status')->default('pending'); // pending, succeeded, failed, cancelled
            $table->foreignId('manager_id')->nullable()->constrained('users')->onDelete('set null');
            $table->foreignId('bot_id')->nullable()->constrained('bots')->onDelete('set null');
            $table->timestamps();
            $table->softDeletes();
            
            // Индексы
            $table->index('order_id');
            $table->index('telegram_id');
            $table->index('status');
            $table->index('payment_status');
            $table->index('created_at');
            $table->index('manager_id');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('orders');
    }
};
