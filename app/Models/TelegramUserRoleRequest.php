<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * Модель заявки на роль пользователя Telegram бота
 * 
 * @property int $id
 * @property int $telegram_user_id
 * @property string $requested_role
 * @property string $status
 * @property string|null $message
 * @property int|null $processed_by
 * @property string|null $rejection_reason
 * @property \Illuminate\Support\Carbon|null $processed_at
 * @property \Illuminate\Support\Carbon $created_at
 * @property \Illuminate\Support\Carbon $updated_at
 * 
 * @property-read TelegramUser $telegramUser
 * @property-read \App\Models\User|null $processedBy
 */
class TelegramUserRoleRequest extends Model
{
    /**
     * Статусы заявок
     */
    const STATUS_PENDING = 'pending';
    const STATUS_APPROVED = 'approved';
    const STATUS_REJECTED = 'rejected';

    /**
     * Запрашиваемые роли
     */
    const ROLE_COURIER = 'courier';
    const ROLE_ADMIN = 'admin';
    const ROLE_KITCHEN = 'kitchen';

    /**
     * Атрибуты, которые можно массово присваивать
     * 
     * @var array<string>
     */
    protected $fillable = [
        'telegram_user_id',
        'requested_role',
        'status',
        'message',
        'processed_by',
        'rejection_reason',
        'processed_at',
    ];

    /**
     * Приведение типов
     * 
     * @var array<string, string>
     */
    protected $casts = [
        'telegram_user_id' => 'integer',
        'processed_by' => 'integer',
        'processed_at' => 'datetime',
        'created_at' => 'datetime',
        'updated_at' => 'datetime',
    ];

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
     * Связь с администратором, который обработал заявку
     * 
     * @return BelongsTo
     */
    public function processedBy(): BelongsTo
    {
        return $this->belongsTo(\App\Models\User::class, 'processed_by', 'id');
    }

    /**
     * Scope для фильтрации заявок по статусу
     * 
     * @param \Illuminate\Database\Eloquent\Builder $query
     * @param string $status
     * @return \Illuminate\Database\Eloquent\Builder
     */
    public function scopeStatus($query, string $status)
    {
        return $query->where('status', $status);
    }

    /**
     * Scope для фильтрации заявок по запрашиваемой роли
     * 
     * @param \Illuminate\Database\Eloquent\Builder $query
     * @param string $role
     * @return \Illuminate\Database\Eloquent\Builder
     */
    public function scopeRequestedRole($query, string $role)
    {
        return $query->where('requested_role', $role);
    }

    /**
     * Scope для получения только активных (pending) заявок
     * 
     * @param \Illuminate\Database\Eloquent\Builder $query
     * @return \Illuminate\Database\Eloquent\Builder
     */
    public function scopePending($query)
    {
        return $query->where('status', self::STATUS_PENDING);
    }
}
