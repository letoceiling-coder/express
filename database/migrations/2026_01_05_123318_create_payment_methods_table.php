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
        Schema::create('payment_methods', function (Blueprint $table) {
            $table->id();
            $table->string('code')->unique(); // yookassa, cash, etc.
            $table->string('name'); // Название для отображения
            $table->text('description')->nullable(); // Описание способа оплаты
            $table->boolean('is_enabled')->default(true); // Включен/выключен
            $table->integer('sort_order')->default(0); // Порядок сортировки
            
            // Настройки скидки
            $table->enum('discount_type', ['none', 'percentage', 'fixed'])->default('none'); // Тип скидки: нет, процент, фиксированная
            $table->decimal('discount_value', 10, 2)->nullable(); // Значение скидки (процент или сумма)
            $table->decimal('min_cart_amount', 10, 2)->nullable(); // Минимальная сумма корзины для применения скидки
            
            // Уведомление пользователя
            $table->boolean('show_notification')->default(false); // Показывать уведомление при выборе
            $table->text('notification_text')->nullable(); // Текст уведомления
            
            // Дополнительные настройки (JSON)
            $table->json('settings')->nullable(); // Дополнительные настройки (например, для ЮКассы)
            
            $table->timestamps();
            $table->softDeletes();
            
            $table->index('is_enabled');
            $table->index('sort_order');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('payment_methods');
    }
};
