<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * Модель истории изменений статусов заказа
 * 
 * @property int $id
 * @property int $order_id
 * @property string $status
 * @property string|null $previous_status
 * @property int|null $changed_by_user_id
 * @property int|null $changed_by_telegram_user_id
 * @property string|null $role
 * @property string|null $comment
 * @property array|null $metadata
 * @property \Illuminate\Support\Carbon $created_at
 * @property \Illuminate\Support\Carbon $updated_at
 * 
 * @property-read Order $order
 * @property-read \App\Models\User|null $changedByUser
 * @property-read TelegramUser|null $changedByTelegramUser
 */
class OrderStatusHistory extends Model
{
    /**
     * Имя таблицы
     * 
     * @var string
     */
    protected $table = 'order_status_history';

    /**
     * Атрибуты, которые можно массово присваивать
     * 
     * @var array<string>
     */
    protected $fillable = [
        'order_id',
        'status',
        'previous_status',
        'changed_by_user_id',
        'changed_by_telegram_user_id',
        'role',
        'comment',
        'metadata',
    ];

    /**
     * Приведение типов
     * 
     * @var array<string, string>
     */
    protected $casts = [
        'order_id' => 'integer',
        'changed_by_user_id' => 'integer',
        'changed_by_telegram_user_id' => 'integer',
        'metadata' => 'array',
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
     * Связь с пользователем админ-панели, изменившим статус
     * 
     * @return BelongsTo
     */
    public function changedByUser(): BelongsTo
    {
        return $this->belongsTo(User::class, 'changed_by_user_id', 'id');
    }

    /**
     * Связь с пользователем бота, изменившим статус
     * 
     * @return BelongsTo
     */
    public function changedByTelegramUser(): BelongsTo
    {
        return $this->belongsTo(TelegramUser::class, 'changed_by_telegram_user_id', 'id');
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
     * Scope для фильтрации по роли
     * 
     * @param \Illuminate\Database\Eloquent\Builder $query
     * @param string $role
     * @return \Illuminate\Database\Eloquent\Builder
     */
    public function scopeByRole($query, string $role)
    {
        return $query->where('role', $role);
    }

    /**
     * Scope для фильтрации по дате (после указанной даты)
     * 
     * @param \Illuminate\Database\Eloquent\Builder $query
     * @param \Carbon\Carbon|string $date
     * @return \Illuminate\Database\Eloquent\Builder
     */
    public function scopeAfterDate($query, $date)
    {
        return $query->where('created_at', '>=', $date);
    }
}
