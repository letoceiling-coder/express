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
        Schema::create('order_items', function (Blueprint $table) {
            $table->id();
            $table->foreignId('order_id')->constrained('orders')->onDelete('cascade');
            $table->foreignId('product_id')->nullable()->constrained('products')->onDelete('set null');
            $table->string('product_name'); // Название товара на момент заказа (для истории)
            $table->string('product_image')->nullable(); // Изображение товара на момент заказа
            $table->integer('quantity');
            $table->decimal('unit_price', 10, 2); // Цена единицы на момент заказа
            $table->decimal('total', 10, 2); // quantity * unit_price
            $table->timestamps();
            
            // Индексы
            $table->index('order_id');
            $table->index('product_id');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('order_items');
    }
};
