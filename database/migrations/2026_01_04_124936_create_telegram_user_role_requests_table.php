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
        Schema::create('telegram_user_role_requests', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('telegram_user_id'); // Связь с пользователем
            $table->string('requested_role'); // courier или admin
            $table->string('status')->default('pending'); // pending, approved, rejected
            $table->text('message')->nullable(); // Сообщение от пользователя (если нужно)
            $table->unsignedBigInteger('processed_by')->nullable(); // ID администратора, который обработал заявку
            $table->text('rejection_reason')->nullable(); // Причина отклонения
            $table->timestamp('processed_at')->nullable(); // Время обработки
            $table->timestamps();

            $table->foreign('telegram_user_id')->references('id')->on('telegram_users')->onDelete('cascade');
            $table->foreign('processed_by')->references('id')->on('users')->onDelete('set null');
            $table->index('telegram_user_id');
            $table->index('status');
            $table->index('requested_role');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('telegram_user_role_requests');
    }
};
