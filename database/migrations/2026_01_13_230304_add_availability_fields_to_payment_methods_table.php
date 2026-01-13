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
        Schema::table('payment_methods', function (Blueprint $table) {
            $table->boolean('available_for_delivery')->default(true)->after('is_enabled')->comment('Доступен для доставки');
            $table->boolean('available_for_pickup')->default(true)->after('available_for_delivery')->comment('Доступен для самовывоза');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('payment_methods', function (Blueprint $table) {
            $table->dropColumn(['available_for_delivery', 'available_for_pickup']);
        });
    }
};
