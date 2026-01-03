<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * Модель возврата товара
 * 
 * @property int $id
 * @property int $order_id
 * @property string $return_number
 * @property string $status
 * @property string $reason
 * @property string $reason_type
 * @property array $items
 * @property float $total_amount
 * @property string|null $refund_method
 * @property string|null $refund_status
 * @property \Illuminate\Support\Carbon|null $refunded_at
 * @property string|null $notes
 * @property string|null $customer_notes
 * @property int|null $processed_by
 * @property \Illuminate\Support\Carbon|null $processed_at
 * @property \Illuminate\Support\Carbon $created_at
 * @property \Illuminate\Support\Carbon $updated_at
 * 
 * @property-read Order $order
 * @property-read User|null $processedBy
 */
class ProductReturn extends Model
{
    /**
     * Имя таблицы
     * 
     * @var string
     */
    protected $table = 'product_returns';

    /**
     * Атрибуты, которые можно массово присваивать
     * 
     * @var array<string>
     */
    protected $fillable = [
        'order_id',
        'return_number',
        'status',
        'reason',
        'reason_type',
        'items',
        'total_amount',
        'refund_method',
        'refund_status',
        'refunded_at',
        'notes',
        'customer_notes',
        'processed_by',
        'processed_at',
    ];

    /**
     * Приведение типов
     * 
     * @var array<string, string>
     */
    protected $casts = [
        'order_id' => 'integer',
        'items' => 'array',
        'total_amount' => 'decimal:2',
        'refunded_at' => 'datetime',
        'processed_by' => 'integer',
        'processed_at' => 'datetime',
        'created_at' => 'datetime',
        'updated_at' => 'datetime',
    ];

    /**
     * Допустимые статусы возврата
     */
    const STATUS_PENDING = 'pending';
    const STATUS_APPROVED = 'approved';
    const STATUS_REJECTED = 'rejected';
    const STATUS_IN_TRANSIT = 'in_transit';
    const STATUS_RECEIVED = 'received';
    const STATUS_REFUNDED = 'refunded';
    const STATUS_CANCELLED = 'cancelled';

    /**
     * Типы причин возврата
     */
    const REASON_DEFECT = 'defect';
    const REASON_WRONG_ITEM = 'wrong_item';
    const REASON_NOT_AS_DESCRIBED = 'not_as_described';
    const REASON_CHANGED_MIND = 'changed_mind';
    const REASON_OTHER = 'other';

    /**
     * Методы возврата
     */
    const REFUND_ORIGINAL = 'original';
    const REFUND_STORE_CREDIT = 'store_credit';
    const REFUND_EXCHANGE = 'exchange';

    /**
     * Boot метод для автогенерации return_number
     */
    protected static function boot()
    {
        parent::boot();

        static::creating(function ($return) {
            if (empty($return->return_number)) {
                $now = now();
                $dateStr = $now->format('Ymd');
                $randomNum = rand(1, 9999);
                $return->return_number = "RET-{$dateStr}-{$randomNum}";
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
     * Связь с пользователем, который обработал возврат
     * 
     * @return BelongsTo
     */
    public function processedBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'processed_by', 'id');
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
}
