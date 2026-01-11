<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Casts\Encrypted;

/**
 * Модель настроек платежной системы
 * 
 * @property int $id
 * @property string $provider
 * @property string|null $shop_id
 * @property string|null $secret_key (encrypted)
 * @property bool $is_test_mode
 * @property bool $is_enabled
 * @property string|null $webhook_url
 * @property array|null $payment_methods
 * @property bool $auto_capture
 * @property string|null $description_template
 * @property string|null $merchant_name
 * @property string|null $test_shop_id
 * @property string|null $test_secret_key (encrypted)
 * @property \Illuminate\Support\Carbon|null $last_test_at
 * @property array|null $last_test_result
 * @property \Illuminate\Support\Carbon $created_at
 * @property \Illuminate\Support\Carbon $updated_at
 */
class PaymentSetting extends Model
{
    /**
     * Имя таблицы
     * 
     * @var string
     */
    protected $table = 'payment_settings';

    /**
     * Атрибуты, которые можно массово присваивать
     * 
     * @var array<string>
     */
    protected $fillable = [
        'provider',
        'shop_id',
        'secret_key',
        'is_test_mode',
        'is_enabled',
        'webhook_url',
        'payment_methods',
        'auto_capture',
        'description_template',
        'merchant_name',
        'test_shop_id',
        'test_secret_key',
        'last_test_at',
        'last_test_result',
    ];

    /**
     * Приведение типов
     * 
     * @var array<string, string>
     */
    protected $casts = [
        'secret_key' => Encrypted::class,
        'test_secret_key' => Encrypted::class,
        'is_test_mode' => 'boolean',
        'is_enabled' => 'boolean',
        'payment_methods' => 'array',
        'auto_capture' => 'boolean',
        'last_test_at' => 'datetime',
        'last_test_result' => 'array',
        'created_at' => 'datetime',
        'updated_at' => 'datetime',
    ];

    /**
     * Получить настройки для конкретного провайдера
     * 
     * @param string $provider
     * @return PaymentSetting|null
     */
    public static function forProvider(string $provider): ?self
    {
        return self::where('provider', $provider)->first();
    }

    /**
     * Получить активный секретный ключ (в зависимости от режима)
     * 
     * @return string|null
     */
    public function getActiveSecretKey(): ?string
    {
        return $this->is_test_mode ? $this->test_secret_key : $this->secret_key;
    }

    /**
     * Получить активный shop_id (в зависимости от режима)
     * 
     * @return string|null
     */
    public function getActiveShopId(): ?string
    {
        return $this->is_test_mode ? $this->test_shop_id : $this->shop_id;
    }
}
