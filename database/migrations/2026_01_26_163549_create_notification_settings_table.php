<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::create('notification_settings', function (Blueprint $table) {
            $table->id();
            $table->string('event')->unique()->comment('Событие: order_created_client, order_created_admin, order_accepted_client');
            $table->boolean('enabled')->default(true)->comment('Включено ли уведомление');
            $table->text('message_template')->nullable()->comment('Шаблон сообщения');
            $table->json('buttons')->nullable()->comment('Кнопки в формате JSON');
            $table->string('support_chat_id')->nullable()->comment('ID чата поддержки для open_chat');
            $table->timestamps();
        });

        // Вставляем дефолтные значения
        $defaultSettings = [
            [
                'event' => 'order_created_client',
                'enabled' => true,
                'message_template' => 'Спасибо! Ваш заказ #{order_id} принят и ожидает подтверждения администратора.',
                'buttons' => null,
                'support_chat_id' => null,
                'created_at' => now(),
                'updated_at' => now(),
            ],
            [
                'event' => 'order_created_admin',
                'enabled' => true,
                'message_template' => null, // Используется форматирование из formatAdminNewOrderMessage
                'buttons' => json_encode([
                    [
                        [
                            'text' => '✅ Принять',
                            'type' => 'callback',
                            'value' => 'order_admin_action:{order_id}:accept'
                        ],
                        [
                            'text' => '❌ Отменить',
                            'type' => 'callback',
                            'value' => 'order_admin_action:{order_id}:cancel'
                        ]
                    ]
                ], JSON_UNESCAPED_UNICODE),
                'support_chat_id' => null,
                'created_at' => now(),
                'updated_at' => now(),
            ],
            [
                'event' => 'order_accepted_client',
                'enabled' => true,
                'message_template' => 'Спасибо! Ваш заказ #{order_id} принят в работу. Мы скоро с вами свяжемся.',
                'buttons' => json_encode([
                    [
                        [
                            'text' => '💬 Написать в поддержку',
                            'type' => 'open_chat',
                            'value' => 'support'
                        ]
                    ]
                ], JSON_UNESCAPED_UNICODE),
                'support_chat_id' => null, // Будет браться из первого администратора или настраиваться отдельно
                'created_at' => now(),
                'updated_at' => now(),
            ],
        ];

        DB::table('notification_settings')->insert($defaultSettings);
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('notification_settings');
    }
};
