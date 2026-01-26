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
        Schema::table('about_page', function (Blueprint $table) {
            $table->boolean('support_enabled')->default(true)->after('support_telegram_url');
            $table->string('support_label')->default('Написать в поддержку')->after('support_enabled');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('about_page', function (Blueprint $table) {
            $table->dropColumn(['support_enabled', 'support_label']);
        });
    }
};
