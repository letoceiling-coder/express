<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * Статистика времени приготовления блюд
 * 
 * @property int $id
 * @property int $order_id
 * @property int $product_id
 * @property string $product_name
 * @property int $quantity
 * @property int $preparation_time_minutes
 * @property int|null $kitchen_user_id
 * @property int $bot_id
 * @property \Illuminate\Support\Carbon $prepared_at
 * @property \Illuminate\Support\Carbon $created_at
 * @property \Illuminate\Support\Carbon $updated_at
 * 
 * @property-read Order $order
 * @property-read Product $product
 * @property-read TelegramUser|null $kitchenUser
 * @property-read Bot $bot
 */
class KitchenPreparationStatistic extends Model
{
    use HasFactory;

    protected $fillable = [
        'order_id',
        'product_id',
        'product_name',
        'quantity',
        'preparation_time_minutes',
        'kitchen_user_id',
        'bot_id',
        'prepared_at',
    ];

    protected $casts = [
        'order_id' => 'integer',
        'product_id' => 'integer',
        'quantity' => 'integer',
        'preparation_time_minutes' => 'integer',
        'kitchen_user_id' => 'integer',
        'bot_id' => 'integer',
        'prepared_at' => 'datetime',
        'created_at' => 'datetime',
        'updated_at' => 'datetime',
    ];

    /**
     * Заказ
     */
    public function order(): BelongsTo
    {
        return $this->belongsTo(Order::class);
    }

    /**
     * Блюдо
     */
    public function product(): BelongsTo
    {
        return $this->belongsTo(Product::class);
    }

    /**
     * Пользователь кухни
     */
    public function kitchenUser(): BelongsTo
    {
        return $this->belongsTo(TelegramUser::class, 'kitchen_user_id');
    }

    /**
     * Бот
     */
    public function bot(): BelongsTo
    {
        return $this->belongsTo(Bot::class);
    }
}
