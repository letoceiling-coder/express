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
        Schema::create('telegram_users', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('bot_id'); // Связь с ботом
            $table->unsignedBigInteger('telegram_id')->unique(); // ID пользователя в Telegram
            $table->string('first_name')->nullable();
            $table->string('last_name')->nullable();
            $table->string('username')->nullable();
            $table->string('language_code')->nullable();
            $table->boolean('is_premium')->default(false);
            $table->boolean('is_blocked')->default(false); // Заблокирован ли пользователь
            $table->timestamp('last_interaction_at')->nullable(); // Последнее взаимодействие
            $table->integer('orders_count')->default(0); // Количество заказов
            $table->decimal('total_spent', 10, 2)->default(0); // Общая сумма покупок
            $table->json('metadata')->nullable(); // Дополнительные данные
            $table->timestamps();
            $table->softDeletes();

            $table->foreign('bot_id')->references('id')->on('bots')->onDelete('cascade');
            $table->index('bot_id');
            $table->index('telegram_id');
            $table->index('is_blocked');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('telegram_users');
    }
};
