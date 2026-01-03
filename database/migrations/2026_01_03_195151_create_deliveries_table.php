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
        Schema::create('deliveries', function (Blueprint $table) {
            $table->id();
            $table->foreignId('order_id')->constrained('orders')->onDelete('cascade');
            $table->string('delivery_type')->default('courier'); // courier, pickup, self_delivery
            $table->string('status')->default('pending'); // pending, assigned, in_transit, delivered, failed, returned
            $table->string('courier_name')->nullable();
            $table->string('courier_phone')->nullable();
            $table->text('delivery_address');
            $table->date('delivery_date')->nullable();
            $table->time('delivery_time_from')->nullable();
            $table->time('delivery_time_to')->nullable();
            $table->timestamp('delivered_at')->nullable();
            $table->decimal('delivery_cost', 10, 2)->default(0);
            $table->text('notes')->nullable();
            $table->string('tracking_number')->nullable()->unique();
            $table->timestamps();
            
            // Индексы
            $table->index('order_id');
            $table->index('status');
            $table->index('delivery_date');
            $table->index('tracking_number');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('deliveries');
    }
};
