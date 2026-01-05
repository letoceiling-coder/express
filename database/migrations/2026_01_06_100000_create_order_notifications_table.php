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
        // Удаляем таблицу, если она существует (на случай неудачной предыдущей миграции)
        Schema::dropIfExists('order_notifications');
        
        Schema::create('order_notifications', function (Blueprint $table) {
            $table->id();
            $table->foreignId('order_id')->constrained('orders')->onDelete('cascade');
            $table->foreignId('telegram_user_id')->constrained('telegram_users')->onDelete('cascade');
            $table->bigInteger('message_id'); // ID сообщения в Telegram
            $table->bigInteger('chat_id'); // ID чата в Telegram
            $table->string('notification_type'); // admin_new, admin_status, client_status, kitchen_order, courier_order
            $table->string('status')->default('active'); // active, updated, deleted
            $table->timestamp('expires_at')->nullable(); // Время истечения уведомления
            $table->timestamps();
            
            // Индексы
            $table->index('order_id');
            $table->index('telegram_user_id');
            $table->index('message_id');
            $table->index('chat_id');
            $table->index('notification_type');
            $table->index('status');
            $table->index('expires_at');
            // Составной индекс для быстрого поиска активных уведомлений клиента
            $table->index(['order_id', 'telegram_user_id', 'notification_type'], 'idx_order_user_type');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('order_notifications');
    }
};

