<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class OrderSetting extends Model
{
    use HasFactory;

    protected $table = 'order_settings';

    protected $fillable = [
        'payment_ttl_minutes',
        'notification_10min_enabled',
        'notification_5min_before_ttl_enabled',
        'notification_auto_cancel_enabled',
        'notification_10min_template',
        'notification_5min_template',
        'notification_auto_cancel_template',
    ];

    protected $casts = [
        'payment_ttl_minutes' => 'integer',
        'notification_10min_enabled' => 'boolean',
        'notification_5min_before_ttl_enabled' => 'boolean',
        'notification_auto_cancel_enabled' => 'boolean',
    ];

    /**
     * Получить настройки (singleton)
     */
    public static function getSettings(): self
    {
        $settings = self::first();
        if (!$settings) {
            $settings = self::create([
                'payment_ttl_minutes' => 180,
                'notification_10min_enabled' => true,
                'notification_5min_before_ttl_enabled' => true,
                'notification_auto_cancel_enabled' => true,
                'notification_10min_template' => 'Вы оформили заказ №{{orderId}} на {{amount}} ₽.\nЧтобы мы начали готовить, оплатите заказ.',
                'notification_5min_template' => 'Заказ №{{orderId}} будет отменён через 5 минут, если не оплатить.',
                'notification_auto_cancel_template' => 'Заказ №{{orderId}} отменён, потому что не был оплачен.',
            ]);
        }
        return $settings;
    }

    /**
     * Заменить плейсхолдеры в шаблоне
     */
    public function replaceTemplatePlaceholders(string $template, array $data): string
    {
        $result = $template;
        foreach ($data as $key => $value) {
            $result = str_replace('{{' . $key . '}}', $value, $result);
        }
        return $result;
    }
}
