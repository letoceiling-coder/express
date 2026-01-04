<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;

/**
 * Модель пользователя Telegram бота
 * 
 * @property int $id
 * @property int $bot_id
 * @property int $telegram_id
 * @property string|null $first_name
 * @property string|null $last_name
 * @property string|null $username
 * @property string|null $language_code
 * @property bool $is_premium
 * @property bool $is_blocked
 * @property \Illuminate\Support\Carbon|null $last_interaction_at
 * @property int $orders_count
 * @property float $total_spent
 * @property array|null $metadata
 * @property \Illuminate\Support\Carbon $created_at
 * @property \Illuminate\Support\Carbon $updated_at
 * @property \Illuminate\Support\Carbon|null $deleted_at
 * 
 * @property-read Bot $bot
 * @property-read \Illuminate\Database\Eloquent\Collection|Order[] $orders
 */
class TelegramUser extends Model
{
    use SoftDeletes;

    /**
     * Атрибуты, которые можно массово присваивать
     * 
     * @var array<string>
     */
    protected $fillable = [
        'bot_id',
        'telegram_id',
        'first_name',
        'last_name',
        'username',
        'language_code',
        'is_premium',
        'is_blocked',
        'last_interaction_at',
        'orders_count',
        'total_spent',
        'metadata',
    ];

    /**
     * Приведение типов
     * 
     * @var array<string, string>
     */
    protected $casts = [
        'bot_id' => 'integer',
        'telegram_id' => 'integer',
        'is_premium' => 'boolean',
        'is_blocked' => 'boolean',
        'last_interaction_at' => 'datetime',
        'orders_count' => 'integer',
        'total_spent' => 'decimal:2',
        'metadata' => 'array',
        'created_at' => 'datetime',
        'updated_at' => 'datetime',
        'deleted_at' => 'datetime',
    ];

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
     * Связь с заказами (через telegram_id)
     * 
     * @return HasMany
     */
    public function orders(): HasMany
    {
        return $this->hasMany(Order::class, 'telegram_id', 'telegram_id');
    }

    /**
     * Получить полное имя пользователя
     * 
     * @return string
     */
    public function getFullNameAttribute(): string
    {
        $parts = array_filter([$this->first_name, $this->last_name]);
        return implode(' ', $parts) ?: ($this->username ?? "User #{$this->telegram_id}");
    }

    /**
     * Scope для фильтрации активных пользователей
     * 
     * @param \Illuminate\Database\Eloquent\Builder $query
     * @return \Illuminate\Database\Eloquent\Builder
     */
    public function scopeActive($query)
    {
        return $query->where('is_blocked', false);
    }

    /**
     * Scope для фильтрации по боту
     * 
     * @param \Illuminate\Database\Eloquent\Builder $query
     * @param int $botId
     * @return \Illuminate\Database\Eloquent\Builder
     */
    public function scopeByBot($query, int $botId)
    {
        return $query->where('bot_id', $botId);
    }

    /**
     * Scope для недавно взаимодействовавших пользователей
     * 
     * @param \Illuminate\Database\Eloquent\Builder $query
     * @param int $days
     * @return \Illuminate\Database\Eloquent\Builder
     */
    public function scopeRecent($query, int $days = 30)
    {
        return $query->where('last_interaction_at', '>=', now()->subDays($days));
    }

    /**
     * Заблокировать пользователя
     * 
     * @return bool
     */
    public function block(): bool
    {
        return $this->update(['is_blocked' => true]);
    }

    /**
     * Разблокировать пользователя
     * 
     * @return bool
     */
    public function unblock(): bool
    {
        return $this->update(['is_blocked' => false]);
    }

    /**
     * Обновить время последнего взаимодействия
     * 
     * @return bool
     */
    public function updateInteraction(): bool
    {
        return $this->update(['last_interaction_at' => now()]);
    }
}
