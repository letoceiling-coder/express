<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * Модель доставки
 * 
 * @property int $id
 * @property int $order_id
 * @property string $delivery_type
 * @property string $status
 * @property string|null $courier_name
 * @property string|null $courier_phone
 * @property string $delivery_address
 * @property string|null $delivery_date
 * @property string|null $delivery_time_from
 * @property string|null $delivery_time_to
 * @property \Illuminate\Support\Carbon|null $delivered_at
 * @property float $delivery_cost
 * @property string|null $notes
 * @property string|null $tracking_number
 * @property \Illuminate\Support\Carbon $created_at
 * @property \Illuminate\Support\Carbon $updated_at
 * 
 * @property-read Order $order
 */
class Delivery extends Model
{
    /**
     * Атрибуты, которые можно массово присваивать
     * 
     * @var array<string>
     */
    protected $fillable = [
        'order_id',
        'delivery_type',
        'status',
        'courier_name',
        'courier_phone',
        'delivery_address',
        'delivery_date',
        'delivery_time_from',
        'delivery_time_to',
        'delivered_at',
        'delivery_cost',
        'notes',
        'tracking_number',
    ];

    /**
     * Приведение типов
     * 
     * @var array<string, string>
     */
    protected $casts = [
        'order_id' => 'integer',
        'delivery_cost' => 'decimal:2',
        'delivery_date' => 'date',
        'delivered_at' => 'datetime',
        'created_at' => 'datetime',
        'updated_at' => 'datetime',
    ];

    /**
     * Допустимые типы доставки
     */
    const TYPE_COURIER = 'courier';
    const TYPE_PICKUP = 'pickup';
    const TYPE_SELF_DELIVERY = 'self_delivery';

    /**
     * Допустимые статусы доставки
     */
    const STATUS_PENDING = 'pending';
    const STATUS_ASSIGNED = 'assigned';
    const STATUS_IN_TRANSIT = 'in_transit';
    const STATUS_DELIVERED = 'delivered';
    const STATUS_FAILED = 'failed';
    const STATUS_RETURNED = 'returned';

    /**
     * Boot метод для автоматического обновления delivered_at
     */
    protected static function boot()
    {
        parent::boot();

        static::updating(function ($delivery) {
            if ($delivery->isDirty('status') && $delivery->status === self::STATUS_DELIVERED) {
                if (!$delivery->delivered_at) {
                    $delivery->delivered_at = now();
                }
            }
        });
    }

    /**
     * Связь с заказом
     * 
     * @return BelongsTo
     */
    public function order(): BelongsTo
    {
        return $this->belongsTo(Order::class, 'order_id', 'id');
    }

    /**
     * Scope для фильтрации по статусу
     * 
     * @param \Illuminate\Database\Eloquent\Builder $query
     * @param string $status
     * @return \Illuminate\Database\Eloquent\Builder
     */
    public function scopeByStatus($query, string $status)
    {
        return $query->where('status', $status);
    }

    /**
     * Scope для фильтрации по типу доставки
     * 
     * @param \Illuminate\Database\Eloquent\Builder $query
     * @param string $type
     * @return \Illuminate\Database\Eloquent\Builder
     */
    public function scopeByType($query, string $type)
    {
        return $query->where('delivery_type', $type);
    }
}
