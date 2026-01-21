<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

/**
 * Модель документа (Политика, Оферта, Контакты)
 * 
 * @property int $id
 * @property string $type
 * @property string $title
 * @property string|null $content
 * @property string|null $url
 * @property bool $is_active
 * @property int $sort_order
 * @property \Illuminate\Support\Carbon $created_at
 * @property \Illuminate\Support\Carbon $updated_at
 */
class Document extends Model
{
    /**
     * Типы документов
     */
    const TYPE_PRIVACY_POLICY = 'privacy_policy';
    const TYPE_OFFER = 'offer';
    const TYPE_CONTACTS = 'contacts';

    /**
     * Атрибуты, которые можно массово присваивать
     * 
     * @var array<string>
     */
    protected $fillable = [
        'type',
        'title',
        'content',
        'url',
        'is_active',
        'sort_order',
    ];

    /**
     * Приведение типов
     * 
     * @var array<string, string>
     */
    protected $casts = [
        'is_active' => 'boolean',
        'sort_order' => 'integer',
        'created_at' => 'datetime',
        'updated_at' => 'datetime',
    ];

    /**
     * Получить документ по типу
     * 
     * @param string $type
     * @return Document|null
     */
    public static function getByType(string $type): ?self
    {
        return self::where('type', $type)
            ->where('is_active', true)
            ->first();
    }

    /**
     * Получить все активные документы
     * 
     * @return \Illuminate\Database\Eloquent\Collection
     */
    public static function getActive(): \Illuminate\Database\Eloquent\Collection
    {
        return self::where('is_active', true)
            ->orderBy('sort_order')
            ->orderBy('title')
            ->get();
    }
}
