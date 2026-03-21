<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     * Fix: добавляет kitchen_timing поля без зависимости от version (которая появляется в add_courier_fields).
     */
    public function up(): void
    {
        Schema::table('orders', function (Blueprint $table) {
            if (!Schema::hasColumn('orders', 'kitchen_started_at')) {
                $table->timestamp('kitchen_started_at')->nullable()->comment('Время начала приготовления (когда кухня приняла заказ)');
            }
            if (!Schema::hasColumn('orders', 'kitchen_ready_at')) {
                $table->timestamp('kitchen_ready_at')->nullable()->comment('Время готовности заказа (когда кухня отметила заказ готовым)');
            }
            if (!Schema::hasColumn('orders', 'preparation_time_minutes')) {
                $table->integer('preparation_time_minutes')->nullable()->comment('Время приготовления в минутах');
            }
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('orders', function (Blueprint $table) {
            $table->dropColumn(['kitchen_started_at', 'kitchen_ready_at', 'preparation_time_minutes']);
        });
    }
};
