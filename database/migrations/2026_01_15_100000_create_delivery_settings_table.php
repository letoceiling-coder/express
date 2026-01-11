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
        Schema::create('delivery_settings', function (Blueprint $table) {
            $table->id();
            $table->string('yandex_geocoder_api_key')->nullable(); // API ключ Яндекс.Геокодера
            $table->text('origin_address')->nullable(); // Адрес начальной точки доставки (текстовый)
            $table->decimal('origin_latitude', 10, 8)->nullable(); // Широта начальной точки
            $table->decimal('origin_longitude', 11, 8)->nullable(); // Долгота начальной точки
            $table->json('delivery_zones')->nullable(); // Зоны доставки: [{"max_distance": 3, "cost": 300}, ...]
            $table->boolean('is_enabled')->default(true); // Включена ли система расчета доставки
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('delivery_settings');
    }
};

