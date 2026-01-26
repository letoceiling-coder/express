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
        Schema::table('telegram_users', function (Blueprint $table) {
            // Флаг, что menu button был установлен (для оптимизации)
            $table->boolean('menu_button_set')->default(false)->after('last_interaction_at');
            // ID последнего приветственного сообщения (для редактирования)
            $table->integer('last_welcome_message_id')->nullable()->after('menu_button_set');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('telegram_users', function (Blueprint $table) {
            $table->dropColumn(['menu_button_set', 'last_welcome_message_id']);
        });
    }
};
