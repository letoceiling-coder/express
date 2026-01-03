<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * Модель отзыва
 * 
 * @property int $id
 * @property int|null $order_id
 * @property int|null $product_id
 * @property int $rating
 * @property string|null $title
 * @property string $comment
 * @property string $customer_name
 * @property string|null $customer_phone
 * @property string|null $customer_email
 * @property string $status
 * @property bool $is_verified_purchase
 * @property int $helpful_count
 * @property array|null $photos
 * @property string|null $response
 * @property \Illuminate\Support\Carbon|null $responded_at
 * @property int|null $responded_by
 * @property \Illuminate\Support\Carbon|null $moderated_at
 * @property int|null $moderated_by
 * @property \Illuminate\Support\Carbon $created_at
 * @property \Illuminate\Support\Carbon $updated_at
 * 
 * @property-read Order|null $order
 * @property-read Product|null $product
 * @property-read User|null $respondedBy
 * @property-read User|null $moderatedBy
 */
class Review extends Model
{
    /**
     * Атрибуты, которые можно массово присваивать
     * 
     * @var array<string>
     */
    protected $fillable = [
        'order_id',
        'product_id',
        'rating',
        'title',
        'comment',
        'customer_name',
        'customer_phone',
        'customer_email',
        'status',
        'is_verified_purchase',
        'helpful_count',
        'photos',
        'response',
        'responded_at',
        'responded_by',
        'moderated_at',
        'moderated_by',
    ];

    /**
     * Приведение типов
     * 
     * @var array<string, string>
     */
    protected $casts = [
        'order_id' => 'integer',
        'product_id' => 'integer',
        'rating' => 'integer',
        'is_verified_purchase' => 'boolean',
        'helpful_count' => 'integer',
        'photos' => 'array',
        'responded_by' => 'integer',
        'moderated_by' => 'integer',
        'responded_at' => 'datetime',
        'moderated_at' => 'datetime',
        'created_at' => 'datetime',
        'updated_at' => 'datetime',
    ];

    /**
     * Статусы отзывов
     */
    const STATUS_PENDING = 'pending';
    const STATUS_APPROVED = 'approved';
    const STATUS_REJECTED = 'rejected';
    const STATUS_HIDDEN = 'hidden';

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
     * Связь с товаром
     * 
     * @return BelongsTo
     */
    public function product(): BelongsTo
    {
        return $this->belongsTo(Product::class, 'product_id', 'id');
    }

    /**
     * Связь с сотрудником, который ответил
     * 
     * @return BelongsTo
     */
    public function respondedBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'responded_by', 'id');
    }

    /**
     * Связь с сотрудником, который модерировал
     * 
     * @return BelongsTo
     */
    public function moderatedBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'moderated_by', 'id');
    }

    /**
     * Получить фотографии
     * 
     * @return \Illuminate\Database\Eloquent\Collection|Media[]
     */
    public function getPhotoFilesAttribute()
    {
        if (empty($this->photos)) {
            return collect([]);
        }

        return Media::whereIn('id', $this->photos)->get();
    }

    /**
     * Scope для одобренных отзывов
     * 
     * @param \Illuminate\Database\Eloquent\Builder $query
     * @return \Illuminate\Database\Eloquent\Builder
     */
    public function scopeApproved($query)
    {
        return $query->where('status', self::STATUS_APPROVED);
    }

    /**
     * Scope для фильтрации по товару
     * 
     * @param \Illuminate\Database\Eloquent\Builder $query
     * @param int $productId
     * @return \Illuminate\Database\Eloquent\Builder
     */
    public function scopeForProduct($query, int $productId)
    {
        return $query->where('product_id', $productId);
    }
}
