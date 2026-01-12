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
        Schema::table('delivery_settings', function (Blueprint $table) {
            $table->decimal('min_delivery_order_total_rub', 10, 2)->default(3000)->after('is_enabled');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('delivery_settings', function (Blueprint $table) {
            $table->dropColumn('min_delivery_order_total_rub');
        });
    }
};
