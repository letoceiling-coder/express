<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

/**
 * Модель настроек доставки
 * 
 * @property int $id
 * @property string|null $yandex_geocoder_api_key
 * @property string|null $origin_address
 * @property float|null $origin_latitude
 * @property float|null $origin_longitude
 * @property array|null $delivery_zones
 * @property bool $is_enabled
 * @property float $min_delivery_order_total_rub
 * @property \Illuminate\Support\Carbon $created_at
 * @property \Illuminate\Support\Carbon $updated_at
 */
class DeliverySetting extends Model
{
    /**
     * Имя таблицы
     * 
     * @var string
     */
    protected $table = 'delivery_settings';

    /**
     * Атрибуты, которые можно массово присваивать
     * 
     * @var array<string>
     */
    protected $fillable = [
        'yandex_geocoder_api_key',
        'origin_address',
        'origin_latitude',
        'origin_longitude',
        'default_city',
        'free_delivery_threshold',
        'delivery_zones',
        'is_enabled',
        'min_delivery_order_total_rub',
        'delivery_min_lead_hours',
    ];

    /**
     * Приведение типов
     * 
     * @var array<string, string>
     */
    protected $casts = [
        'origin_latitude' => 'decimal:8',
        'origin_longitude' => 'decimal:8',
        'delivery_zones' => 'array',
        'is_enabled' => 'boolean',
        'free_delivery_threshold' => 'decimal:2',
        'min_delivery_order_total_rub' => 'decimal:2',
        'delivery_min_lead_hours' => 'integer',
        'created_at' => 'datetime',
        'updated_at' => 'datetime',
    ];

    /**
     * Получить единственную запись настроек доставки (singleton)
     * 
     * @return DeliverySetting
     */
    public static function getSettings(): self
    {
        $settings = self::first();
        
        if (!$settings) {
            // Создаем настройки по умолчанию
            $settings = self::create([
                'default_city' => 'Екатеринбург',
                'free_delivery_threshold' => 10000,
                'delivery_zones' => [
                    ['max_distance' => 3, 'cost' => 300],
                    ['max_distance' => 7, 'cost' => 500],
                    ['max_distance' => 12, 'cost' => 800],
                    ['max_distance' => null, 'cost' => 1000], // свыше 12 км
                ],
                'is_enabled' => false, // По умолчанию выключено до настройки
                'min_delivery_order_total_rub' => 3000,
                'delivery_min_lead_hours' => 3,
            ]);
        }
        
        return $settings;
    }

    /**
     * Получить стоимость доставки по расстоянию
     * 
     * @param float $distance Расстояние в км
     * @param float $cartTotal Сумма корзины
     * @return float Стоимость доставки
     */
    public function getDeliveryCost(float $distance, float $cartTotal = 0): float
    {
        // Проверка бесплатной доставки
        if ($this->free_delivery_threshold > 0 && $cartTotal >= $this->free_delivery_threshold) {
            return 0.00;
        }

        $zones = $this->delivery_zones ?? [];
        
        // Сортируем зоны по max_distance (по возрастанию)
        usort($zones, function ($a, $b) {
            $maxA = $a['max_distance'] ?? PHP_FLOAT_MAX;
            $maxB = $b['max_distance'] ?? PHP_FLOAT_MAX;
            return $maxA <=> $maxB;
        });
        
        // Ищем подходящую зону
        foreach ($zones as $zone) {
            $maxDistance = $zone['max_distance'] ?? null;
            $cost = (float) ($zone['cost'] ?? 0);
            
            // Если max_distance null, это последняя зона (свыше предыдущей)
            if ($maxDistance === null) {
                return $cost;
            }
            
            // Если расстояние меньше или равно максимальному для зоны
            if ($distance <= $maxDistance) {
                return $cost;
            }
        }
        
        // Если не нашли подходящую зону, возвращаем стоимость последней зоны
        $lastZone = end($zones);
        return (float) ($lastZone['cost'] ?? 1000);
    }
}

