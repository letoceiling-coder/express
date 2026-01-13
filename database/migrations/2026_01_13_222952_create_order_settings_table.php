<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::create('order_settings', function (Blueprint $table) {
            $table->id();
            $table->integer('payment_ttl_minutes')->default(180)->comment('TTL для неоплаченных заказов в минутах');
            
            // Настройки уведомлений
            $table->boolean('notification_10min_enabled')->default(true)->comment('Уведомление через 10 минут');
            $table->boolean('notification_5min_before_ttl_enabled')->default(true)->comment('Уведомление за 5 минут до TTL');
            $table->boolean('notification_auto_cancel_enabled')->default(true)->comment('Уведомление об авто-отмене');
            
            // Шаблоны сообщений
            $table->text('notification_10min_template')->nullable()->comment('Шаблон уведомления через 10 минут');
            $table->text('notification_5min_template')->nullable()->comment('Шаблон уведомления за 5 минут до TTL');
            $table->text('notification_auto_cancel_template')->nullable()->comment('Шаблон уведомления об авто-отмене');
            
            $table->timestamps();
        });

        // Вставляем дефолтные значения
        DB::table('order_settings')->insert([
            'payment_ttl_minutes' => 180,
            'notification_10min_enabled' => true,
            'notification_5min_before_ttl_enabled' => true,
            'notification_auto_cancel_enabled' => true,
            'notification_10min_template' => 'Вы оформили заказ №{{orderId}} на {{amount}} ₽.\nЧтобы мы начали готовить, оплатите заказ.',
            'notification_5min_template' => 'Заказ №{{orderId}} будет отменён через 5 минут, если не оплатить.',
            'notification_auto_cancel_template' => 'Заказ №{{orderId}} отменён, потому что не был оплачен.',
            'created_at' => now(),
            'updated_at' => now(),
        ]);
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('order_settings');
    }
};
