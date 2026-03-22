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
        Schema::table('sms_codes', function (Blueprint $table) {
            $table->timestamp('used_at')->nullable()->after('attempts');
            $table->string('ip', 45)->nullable()->after('used_at');
            $table->string('user_agent', 500)->nullable()->after('ip');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('sms_codes', function (Blueprint $table) {
            $table->dropColumn(['used_at', 'ip', 'user_agent']);
        });
    }
};
