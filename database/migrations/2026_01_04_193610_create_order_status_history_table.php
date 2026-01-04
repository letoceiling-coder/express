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
        Schema::create('order_status_history', function (Blueprint $table) {
            $table->id();
            $table->foreignId('order_id')->constrained('orders')->onDelete('cascade');
            $table->string('status'); // Новый статус
            $table->string('previous_status')->nullable(); // Предыдущий статус
            $table->foreignId('changed_by_user_id')->nullable()->constrained('users')->onDelete('set null'); // ID пользователя админ-панели
            $table->foreignId('changed_by_telegram_user_id')->nullable()->constrained('telegram_users')->onDelete('set null'); // ID пользователя бота
            $table->string('role')->nullable(); // Роль пользователя (admin, kitchen, courier, user)
            $table->text('comment')->nullable(); // Комментарий к изменению
            $table->json('metadata')->nullable(); // Дополнительные данные
            $table->timestamps();
            
            // Индексы
            $table->index('order_id');
            $table->index('status');
            $table->index('created_at');
            $table->index('role');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('order_status_history');
    }
};
