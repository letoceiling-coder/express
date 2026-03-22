<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('sms_codes', function (Blueprint $table) {
            $table->string('device_id', 100)->nullable()->after('user_agent');
        });
    }

    public function down(): void
    {
        Schema::table('sms_codes', function (Blueprint $table) {
            $table->dropColumn('device_id');
        });
    }
};
