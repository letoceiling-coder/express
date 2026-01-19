<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Support\Str;

/**
 * Модель товара/продукта
 * 
 * @property int $id
 * @property string $name
 * @property string $slug
 * @property string|null $description
 * @property string|null $short_description
 * @property float $price
 * @property float|null $compare_price
 * @property int|null $category_id
 * @property string|null $sku
 * @property string|null $barcode
 * @property int $stock_quantity
 * @property bool $is_available
 * @property bool $is_weight_product
 * @property float|null $weight
 * @property int|null $image_id
 * @property array|null $gallery_ids
 * @property int|null $video_id
 * @property int $sort_order
 * @property string|null $meta_title
 * @property string|null $meta_description
 * @property \Illuminate\Support\Carbon $created_at
 * @property \Illuminate\Support\Carbon $updated_at
 * 
 * @property-read Category|null $category
 * @property-read Media|null $image
 * @property-read Media|null $video
 * @property-read \Illuminate\Database\Eloquent\Collection|Media[] $gallery
 */
class Product extends Model
{
    /**
     * Атрибуты, которые можно массово присваивать
     * 
     * @var array<string>
     */
    protected $fillable = [
        'name',
        'slug',
        'description',
        'short_description',
        'price',
        'compare_price',
        'category_id',
        'sku',
        'barcode',
        'stock_quantity',
        'is_available',
        'is_weight_product',
        'weight',
        'image_id',
        'gallery_ids',
        'video_id',
        'sort_order',
        'meta_title',
        'meta_description',
        'position',
    ];

    /**
     * Приведение типов
     * 
     * @var array<string, string>
     */
    protected $casts = [
        'price' => 'decimal:2',
        'compare_price' => 'decimal:2',
        'category_id' => 'integer',
        'stock_quantity' => 'integer',
        'is_available' => 'boolean',
        'is_weight_product' => 'boolean',
        'weight' => 'decimal:2',
        'image_id' => 'integer',
        'gallery_ids' => 'array',
        'video_id' => 'integer',
        'sort_order' => 'integer',
        'position' => 'integer',
        'created_at' => 'datetime',
        'updated_at' => 'datetime',
    ];

    /**
     * Boot метод для автогенерации slug
     */
    protected static function boot()
    {
        parent::boot();

        static::creating(function ($product) {
            if (empty($product->slug)) {
                $product->slug = Str::slug($product->name);
            }
        });

        static::updating(function ($product) {
            if ($product->isDirty('name') && empty($product->slug)) {
                $product->slug = Str::slug($product->name);
            }
        });
    }

    /**
     * Связь с категорией
     * 
     * @return BelongsTo
     */
    public function category(): BelongsTo
    {
        return $this->belongsTo(Category::class, 'category_id', 'id');
    }

    /**
     * Связь с главным изображением
     * 
     * @return BelongsTo
     */
    public function image(): BelongsTo
    {
        return $this->belongsTo(Media::class, 'image_id', 'id');
    }

    /**
     * Связь с видео
     * 
     * @return BelongsTo
     */
    public function video(): BelongsTo
    {
        return $this->belongsTo(Media::class, 'video_id', 'id');
    }

    /**
     * Получить галерею изображений
     * 
     * @return \Illuminate\Database\Eloquent\Collection|Media[]
     */
    public function getGalleryAttribute()
    {
        if (empty($this->gallery_ids)) {
            return collect([]);
        }

        return Media::whereIn('id', $this->gallery_ids)->get();
    }

    /**
     * Scope для доступных товаров
     * 
     * @param \Illuminate\Database\Eloquent\Builder $query
     * @return \Illuminate\Database\Eloquent\Builder
     */
    public function scopeAvailable($query)
    {
        return $query->where('is_available', true);
    }

    /**
     * Scope для фильтрации по категории
     * 
     * @param \Illuminate\Database\Eloquent\Builder $query
     * @param int|null $categoryId
     * @return \Illuminate\Database\Eloquent\Builder
     */
    public function scopeInCategory($query, ?int $categoryId)
    {
        if ($categoryId === null) {
            return $query->whereNull('category_id');
        }

        return $query->where('category_id', $categoryId);
    }

    /**
     * Проверить, есть ли скидка
     * 
     * @return bool
     */
    public function hasDiscount(): bool
    {
        return $this->compare_price !== null && $this->compare_price > $this->price;
    }

    /**
     * Получить процент скидки
     * 
     * @return float|null
     */
    public function getDiscountPercentAttribute(): ?float
    {
        if (!$this->hasDiscount()) {
            return null;
        }

        return round((($this->compare_price - $this->price) / $this->compare_price) * 100, 2);
    }

    /**
     * Проверить наличие на складе
     * 
     * @return bool
     */
    public function inStock(): bool
    {
        if ($this->is_weight_product) {
            return $this->stock_quantity > 0;
        }

        return $this->stock_quantity > 0;
    }

    /**
     * Scope для сортировки по позиции
     * 
     * @param \Illuminate\Database\Eloquent\Builder $query
     * @return \Illuminate\Database\Eloquent\Builder
     */
    public function scopeOrdered($query)
    {
        return $query->orderBy('position', 'asc')->orderBy('id', 'asc');
    }
}
