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
            $table->foreignId('courier_id')->nullable()->after('bot_id')->constrained('telegram_users')->onDelete('set null');
            $table->boolean('assigned_to_all_couriers')->default(false)->after('courier_id');
            $table->unsignedInteger('version')->default(1)->after('assigned_to_all_couriers');
            
            // Индексы
            $table->index('courier_id');
            $table->index('version');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('orders', function (Blueprint $table) {
            $table->dropForeign(['courier_id']);
            $table->dropIndex(['courier_id']);
            $table->dropIndex(['version']);
            $table->dropColumn(['courier_id', 'assigned_to_all_couriers', 'version']);
        });
    }
};

