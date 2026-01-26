<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * Модель платежа
 * 
 * @property int $id
 * @property int $order_id
 * @property string $payment_method
 * @property string|null $payment_provider
 * @property string $status
 * @property float $amount
 * @property string $currency
 * @property string|null $transaction_id
 * @property array|null $provider_response
 * @property \Illuminate\Support\Carbon|null $paid_at
 * @property float $refunded_amount
 * @property \Illuminate\Support\Carbon|null $refunded_at
 * @property string|null $notes
 * @property \Illuminate\Support\Carbon $created_at
 * @property \Illuminate\Support\Carbon $updated_at
 * 
 * @property-read Order $order
 */
class Payment extends Model
{
    /**
     * Атрибуты, которые можно массово присваивать
     * 
     * @var array<string>
     */
    protected $fillable = [
        'order_id',
        'payment_method',
        'payment_provider',
        'status',
        'amount',
        'currency',
        'transaction_id',
        'provider_response',
        'paid_at',
        'captured_at',
        'refunded_amount',
        'refunded_at',
        'notes',
    ];

    /**
     * Приведение типов
     * 
     * @var array<string, string>
     */
    protected $casts = [
        'order_id' => 'integer',
        'amount' => 'decimal:2',
        'refunded_amount' => 'decimal:2',
        'provider_response' => 'array',
        'paid_at' => 'datetime',
        'captured_at' => 'datetime',
        'refunded_at' => 'datetime',
        'created_at' => 'datetime',
        'updated_at' => 'datetime',
    ];

    /**
     * Допустимые методы оплаты
     */
    const METHOD_CARD = 'card';
    const METHOD_CASH = 'cash';
    const METHOD_ONLINE = 'online';
    const METHOD_BANK_TRANSFER = 'bank_transfer';
    const METHOD_OTHER = 'other';

    /**
     * Допустимые статусы платежа
     */
    const STATUS_PENDING = 'pending';
    const STATUS_PROCESSING = 'processing';
    const STATUS_SUCCEEDED = 'succeeded';
    const STATUS_FAILED = 'failed';
    const STATUS_REFUNDED = 'refunded';
    const STATUS_PARTIALLY_REFUNDED = 'partially_refunded';
    const STATUS_CANCELLED = 'cancelled';

    /**
     * Boot метод для автоматического обновления paid_at
     */
    protected static function boot()
    {
        parent::boot();

        static::updating(function ($payment) {
            if ($payment->isDirty('status') && $payment->status === self::STATUS_SUCCEEDED) {
                if (!$payment->paid_at) {
                    $payment->paid_at = now();
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
     * Scope для фильтрации по методу оплаты
     * 
     * @param \Illuminate\Database\Eloquent\Builder $query
     * @param string $method
     * @return \Illuminate\Database\Eloquent\Builder
     */
    public function scopeByMethod($query, string $method)
    {
        return $query->where('payment_method', $method);
    }

    /**
     * Проверить, можно ли вернуть платеж
     * 
     * @return bool
     */
    public function canRefund(): bool
    {
        return $this->status === self::STATUS_SUCCEEDED;
    }

    /**
     * Получить доступную сумму для возврата
     * 
     * @return float
     */
    public function getAvailableRefundAmountAttribute(): float
    {
        return (float) $this->amount - (float) $this->refunded_amount;
    }
}
