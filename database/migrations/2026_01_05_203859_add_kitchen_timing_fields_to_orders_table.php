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
        Schema::table('orders', function (Blueprint $table) {
            $table->timestamp('kitchen_started_at')->nullable()->after('version')->comment('Время начала приготовления (когда кухня приняла заказ)');
            $table->timestamp('kitchen_ready_at')->nullable()->after('kitchen_started_at')->comment('Время готовности заказа (когда кухня отметила заказ готовым)');
            $table->integer('preparation_time_minutes')->nullable()->after('kitchen_ready_at')->comment('Время приготовления в минутах');
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
