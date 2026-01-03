<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Str;

/**
 * Модель категории товаров
 * 
 * @property int $id
 * @property string $name
 * @property string $slug
 * @property string|null $description
 * @property int|null $image_id
 * @property int $sort_order
 * @property bool $is_active
 * @property string|null $meta_title
 * @property string|null $meta_description
 * @property \Illuminate\Support\Carbon $created_at
 * @property \Illuminate\Support\Carbon $updated_at
 * 
 * @property-read Media|null $image
 */
class Category extends Model
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
        'image_id',
        'sort_order',
        'is_active',
        'meta_title',
        'meta_description',
    ];

    /**
     * Приведение типов
     * 
     * @var array<string, string>
     */
    protected $casts = [
        'image_id' => 'integer',
        'sort_order' => 'integer',
        'is_active' => 'boolean',
        'created_at' => 'datetime',
        'updated_at' => 'datetime',
    ];

    /**
     * Boot метод для автогенерации slug
     */
    protected static function boot()
    {
        parent::boot();

        static::creating(function ($category) {
            if (empty($category->slug)) {
                $category->slug = Str::slug($category->name);
            }
        });

        static::updating(function ($category) {
            if ($category->isDirty('name') && empty($category->slug)) {
                $category->slug = Str::slug($category->name);
            }
        });
    }

    /**
     * Связь с изображением
     * 
     * @return BelongsTo
     */
    public function image(): BelongsTo
    {
        return $this->belongsTo(Media::class, 'image_id', 'id');
    }

    /**
     * Scope для активных категорий
     * 
     * @param \Illuminate\Database\Eloquent\Builder $query
     * @return \Illuminate\Database\Eloquent\Builder
     */
    public function scopeActive($query)
    {
        return $query->where('is_active', true);
    }

    /**
     * Scope для сортировки
     * 
     * @param \Illuminate\Database\Eloquent\Builder $query
     * @return \Illuminate\Database\Eloquent\Builder
     */
    public function scopeOrdered($query)
    {
        return $query->orderBy('sort_order', 'asc')->orderBy('name', 'asc');
    }
}
