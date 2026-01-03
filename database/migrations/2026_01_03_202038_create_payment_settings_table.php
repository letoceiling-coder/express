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
        Schema::create('payment_settings', function (Blueprint $table) {
            $table->id();
            $table->string('provider')->default('yookassa')->unique(); // 'yookassa', 'sberbank', etc.
            $table->string('shop_id')->nullable(); // ID магазина
            $table->text('secret_key')->nullable(); // шифруется через Laravel encryption
            $table->boolean('is_test_mode')->default(true);
            $table->boolean('is_enabled')->default(false);
            $table->string('webhook_url')->nullable();
            $table->json('payment_methods')->nullable(); // разрешенные методы оплаты
            $table->boolean('auto_capture')->default(true);
            $table->string('description_template')->nullable();
            $table->string('test_shop_id')->nullable();
            $table->text('test_secret_key')->nullable(); // шифруется
            $table->timestamp('last_test_at')->nullable();
            $table->json('last_test_result')->nullable();
            $table->timestamps();
            
            // Индексы
            $table->index('provider');
            $table->index('is_enabled');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('payment_settings');
    }
};
