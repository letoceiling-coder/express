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
            $table->string('support_telegram_url')->nullable()->after('yandex_maps_url');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('about_page', function (Blueprint $table) {
            $table->dropColumn('support_telegram_url');
        });
    }
};
