<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     * Fix: delivery_settings создаётся в 2026_01_15, а 2026_01_12 пытается добавить колонку раньше.
     */
    public function up(): void
    {
        if (!Schema::hasTable('delivery_settings')) {
            return;
        }
        if (!Schema::hasColumn('delivery_settings', 'min_delivery_order_total_rub')) {
            Schema::table('delivery_settings', function (Blueprint $table) {
                $table->decimal('min_delivery_order_total_rub', 10, 2)->default(3000)->after('is_enabled');
            });
        }
    }

    public function down(): void
    {
        if (Schema::hasColumn('delivery_settings', 'min_delivery_order_total_rub')) {
            Schema::table('delivery_settings', function (Blueprint $table) {
                $table->dropColumn('min_delivery_order_total_rub');
            });
        }
    }
};
