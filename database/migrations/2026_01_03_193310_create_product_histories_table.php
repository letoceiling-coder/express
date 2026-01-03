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
        Schema::create('product_histories', function (Blueprint $table) {
            $table->id();
            $table->foreignId('product_id')->constrained('products')->onDelete('cascade');
            $table->foreignId('user_id')->nullable()->constrained('users')->onDelete('set null');
            $table->string('action'); // 'created', 'updated', 'deleted', 'restored'
            $table->string('field_name')->nullable(); // какое поле изменилось
            $table->text('old_value')->nullable(); // старое значение (JSON для объектов)
            $table->text('new_value')->nullable(); // новое значение (JSON для объектов)
            $table->json('changes')->nullable(); // все изменения одним объектом
            $table->timestamps();
            
            // Индексы
            $table->index('product_id');
            $table->index('user_id');
            $table->index('action');
            $table->index('created_at');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('product_histories');
    }
};
