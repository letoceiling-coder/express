<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * Модель истории изменений товара
 * 
 * @property int $id
 * @property int $product_id
 * @property int|null $user_id
 * @property string $action
 * @property string|null $field_name
 * @property string|null $old_value
 * @property string|null $new_value
 * @property array|null $changes
 * @property \Illuminate\Support\Carbon $created_at
 * @property \Illuminate\Support\Carbon $updated_at
 * 
 * @property-read Product $product
 * @property-read User|null $user
 */
class ProductHistory extends Model
{
    /**
     * Атрибуты, которые можно массово присваивать
     * 
     * @var array<string>
     */
    protected $fillable = [
        'product_id',
        'user_id',
        'action',
        'field_name',
        'old_value',
        'new_value',
        'changes',
    ];

    /**
     * Приведение типов
     * 
     * @var array<string, string>
     */
    protected $casts = [
        'product_id' => 'integer',
        'user_id' => 'integer',
        'changes' => 'array',
        'created_at' => 'datetime',
        'updated_at' => 'datetime',
    ];

    /**
     * Связь с товаром
     * 
     * @return BelongsTo
     */
    public function product(): BelongsTo
    {
        return $this->belongsTo(Product::class, 'product_id', 'id');
    }

    /**
     * Связь с пользователем
     * 
     * @return BelongsTo
     */
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class, 'user_id', 'id');
    }

    /**
     * Scope для фильтрации по действию
     * 
     * @param \Illuminate\Database\Eloquent\Builder $query
     * @param string $action
     * @return \Illuminate\Database\Eloquent\Builder
     */
    public function scopeByAction($query, string $action)
    {
        return $query->where('action', $action);
    }

    /**
     * Scope для фильтрации по полю
     * 
     * @param \Illuminate\Database\Eloquent\Builder $query
     * @param string $fieldName
     * @return \Illuminate\Database\Eloquent\Builder
     */
    public function scopeByField($query, string $fieldName)
    {
        return $query->where('field_name', $fieldName);
    }
}
