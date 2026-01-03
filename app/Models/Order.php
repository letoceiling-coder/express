<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;

/**
 * Модель заказа
 * 
 * @property int $id
 * @property string $order_id
 * @property int $telegram_id
 * @property string $status
 * @property string $phone
 * @property string $delivery_address
 * @property string $delivery_type
 * @property string $delivery_time
 * @property string|null $delivery_date
 * @property string|null $delivery_time_from
 * @property string|null $delivery_time_to
 * @property float $delivery_cost
 * @property string|null $comment
 * @property string|null $notes
 * @property float $total_amount
 * @property string|null $payment_id
 * @property string $payment_status
 * @property int|null $manager_id
 * @property int|null $bot_id
 * @property \Illuminate\Support\Carbon $created_at
 * @property \Illuminate\Support\Carbon $updated_at
 * @property \Illuminate\Support\Carbon|null $deleted_at
 * 
 * @property-read \Illuminate\Database\Eloquent\Collection|OrderItem[] $items
 * @property-read User|null $manager
 * @property-read Bot|null $bot
 */
class Order extends Model
{
    use SoftDeletes;

    /**
     * Атрибуты, которые можно массово присваивать
     * 
     * @var array<string>
     */
    protected $fillable = [
        'order_id',
        'telegram_id',
        'status',
        'phone',
        'delivery_address',
        'delivery_type',
        'delivery_time',
        'delivery_date',
        'delivery_time_from',
        'delivery_time_to',
        'delivery_cost',
        'comment',
        'notes',
        'total_amount',
        'payment_id',
        'payment_status',
        'manager_id',
        'bot_id',
    ];

    /**
     * Приведение типов
     * 
     * @var array<string, string>
     */
    protected $casts = [
        'telegram_id' => 'integer',
        'delivery_cost' => 'decimal:2',
        'total_amount' => 'decimal:2',
        'manager_id' => 'integer',
        'bot_id' => 'integer',
        'delivery_date' => 'date',
        'created_at' => 'datetime',
        'updated_at' => 'datetime',
        'deleted_at' => 'datetime',
    ];

    /**
     * Допустимые статусы заказа
     */
    const STATUS_NEW = 'new';
    const STATUS_ACCEPTED = 'accepted';
    const STATUS_PREPARING = 'preparing';
    const STATUS_READY_FOR_DELIVERY = 'ready_for_delivery';
    const STATUS_IN_TRANSIT = 'in_transit';
    const STATUS_DELIVERED = 'delivered';
    const STATUS_CANCELLED = 'cancelled';

    /**
     * Допустимые статусы оплаты
     */
    const PAYMENT_STATUS_PENDING = 'pending';
    const PAYMENT_STATUS_SUCCEEDED = 'succeeded';
    const PAYMENT_STATUS_FAILED = 'failed';
    const PAYMENT_STATUS_CANCELLED = 'cancelled';

    /**
     * Связь с элементами заказа
     * 
     * @return HasMany
     */
    public function items(): HasMany
    {
        return $this->hasMany(OrderItem::class, 'order_id', 'id');
    }

    /**
     * Связь с менеджером
     * 
     * @return BelongsTo
     */
    public function manager(): BelongsTo
    {
        return $this->belongsTo(User::class, 'manager_id', 'id');
    }

    /**
     * Связь с ботом
     * 
     * @return BelongsTo
     */
    public function bot(): BelongsTo
    {
        return $this->belongsTo(Bot::class, 'bot_id', 'id');
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
     * Scope для фильтрации по статусу оплаты
     * 
     * @param \Illuminate\Database\Eloquent\Builder $query
     * @param string $paymentStatus
     * @return \Illuminate\Database\Eloquent\Builder
     */
    public function scopeByPaymentStatus($query, string $paymentStatus)
    {
        return $query->where('payment_status', $paymentStatus);
    }

    /**
     * Scope для фильтрации по telegram_id
     * 
     * @param \Illuminate\Database\Eloquent\Builder $query
     * @param int $telegramId
     * @return \Illuminate\Database\Eloquent\Builder
     */
    public function scopeByTelegramId($query, int $telegramId)
    {
        return $query->where('telegram_id', $telegramId);
    }

    /**
     * Проверить, можно ли изменить статус
     * 
     * @param string $newStatus
     * @return bool
     */
    public function canChangeStatus(string $newStatus): bool
    {
        // Нельзя изменить статус доставлен или отменен
        if (in_array($this->status, [self::STATUS_DELIVERED, self::STATUS_CANCELLED])) {
            return false;
        }

        return true;
    }

    /**
     * Получить полную сумму заказа (с доставкой)
     * 
     * @return float
     */
    public function getTotalWithDeliveryAttribute(): float
    {
        return (float) $this->total_amount + (float) $this->delivery_cost;
    }
}
