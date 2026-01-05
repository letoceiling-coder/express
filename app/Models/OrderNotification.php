<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * Модель уведомлений о заказах
 * 
 * @property int $id
 * @property int $order_id
 * @property int $telegram_user_id
 * @property int $message_id
 * @property int $chat_id
 * @property string $notification_type
 * @property string $status
 * @property \Illuminate\Support\Carbon|null $expires_at
 * @property \Illuminate\Support\Carbon $created_at
 * @property \Illuminate\Support\Carbon $updated_at
 * 
 * @property-read Order $order
 * @property-read TelegramUser $telegramUser
 */
class OrderNotification extends Model
{
    use HasFactory;

    /**
     * Типы уведомлений
     */
    const TYPE_ADMIN_NEW = 'admin_new';
    const TYPE_ADMIN_STATUS = 'admin_status';
    const TYPE_CLIENT_STATUS = 'client_status';
    const TYPE_KITCHEN_ORDER = 'kitchen_order';
    const TYPE_COURIER_ORDER = 'courier_order';

    /**
     * Статусы уведомлений
     */
    const STATUS_ACTIVE = 'active';
    const STATUS_UPDATED = 'updated';
    const STATUS_DELETED = 'deleted';

    /**
     * Атрибуты, которые можно массово присваивать
     * 
     * @var array<string>
     */
    protected $fillable = [
        'order_id',
        'telegram_user_id',
        'message_id',
        'chat_id',
        'notification_type',
        'status',
        'expires_at',
    ];

    /**
     * Приведение типов
     * 
     * @var array<string, string>
     */
    protected $casts = [
        'order_id' => 'integer',
        'telegram_user_id' => 'integer',
        'message_id' => 'integer',
        'chat_id' => 'integer',
        'expires_at' => 'datetime',
        'created_at' => 'datetime',
        'updated_at' => 'datetime',
    ];

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
     * Связь с пользователем Telegram
     * 
     * @return BelongsTo
     */
    public function telegramUser(): BelongsTo
    {
        return $this->belongsTo(TelegramUser::class, 'telegram_user_id', 'id');
    }

    /**
     * Scope для фильтрации активных уведомлений
     * 
     * @param \Illuminate\Database\Eloquent\Builder $query
     * @return \Illuminate\Database\Eloquent\Builder
     */
    public function scopeActive($query)
    {
        return $query->where('status', self::STATUS_ACTIVE);
    }

    /**
     * Scope для фильтрации по типу
     * 
     * @param \Illuminate\Database\Eloquent\Builder $query
     * @param string $type
     * @return \Illuminate\Database\Eloquent\Builder
     */
    public function scopeByType($query, string $type)
    {
        return $query->where('notification_type', $type);
    }

    /**
     * Scope для фильтрации просроченных уведомлений
     * 
     * @param \Illuminate\Database\Eloquent\Builder $query
     * @return \Illuminate\Database\Eloquent\Builder
     */
    public function scopeExpired($query)
    {
        return $query->where('expires_at', '<', now())
            ->where('status', self::STATUS_ACTIVE);
    }

    /**
     * Пометить уведомление как обновленное
     * 
     * @return bool
     */
    public function markAsUpdated(): bool
    {
        return $this->update(['status' => self::STATUS_UPDATED]);
    }

    /**
     * Пометить уведомление как удаленное
     * 
     * @return bool
     */
    public function markAsDeleted(): bool
    {
        return $this->update(['status' => self::STATUS_DELETED]);
    }

    /**
     * Проверить, истекло ли уведомление
     * 
     * @return bool
     */
    public function isExpired(): bool
    {
        return $this->expires_at && $this->expires_at->isPast();
    }
}

