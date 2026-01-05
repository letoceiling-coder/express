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
        Schema::create('kitchen_preparation_statistics', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('order_id')->comment('ID заказа');
            $table->unsignedBigInteger('product_id')->comment('ID блюда');
            $table->string('product_name')->comment('Название блюда');
            $table->integer('quantity')->default(1)->comment('Количество порций');
            $table->integer('preparation_time_minutes')->comment('Время приготовления в минутах');
            $table->unsignedBigInteger('kitchen_user_id')->nullable()->comment('ID пользователя кухни, который готовил');
            $table->unsignedBigInteger('bot_id')->comment('ID бота');
            $table->timestamp('prepared_at')->comment('Дата и время приготовления');
            $table->timestamps();

            $table->foreign('order_id')->references('id')->on('orders')->onDelete('cascade');
            $table->foreign('product_id')->references('id')->on('products')->onDelete('cascade');
            $table->foreign('kitchen_user_id')->references('id')->on('telegram_users')->onDelete('set null');
            $table->foreign('bot_id')->references('id')->on('bots')->onDelete('cascade');

            $table->index(['product_id', 'prepared_at']);
            $table->index(['bot_id', 'prepared_at']);
            $table->index('kitchen_user_id');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('kitchen_preparation_statistics');
    }
};
