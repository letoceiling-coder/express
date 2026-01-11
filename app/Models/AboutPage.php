<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

/**
 * Модель страницы "О нас"
 * 
 * @property int $id
 * @property string $title
 * @property string|null $phone
 * @property string|null $address
 * @property string|null $description
 * @property array|null $bullets
 * @property string|null $yandex_maps_url
 * @property string|null $cover_image_url
 * @property \Illuminate\Support\Carbon $created_at
 * @property \Illuminate\Support\Carbon $updated_at
 */
class AboutPage extends Model
{
    /**
     * Имя таблицы
     * 
     * @var string
     */
    protected $table = 'about_page';

    /**
     * Атрибуты, которые можно массово присваивать
     * 
     * @var array<string>
     */
    protected $fillable = [
        'title',
        'phone',
        'address',
        'description',
        'bullets',
        'yandex_maps_url',
        'cover_image_url',
    ];

    /**
     * Приведение типов
     * 
     * @var array<string, string>
     */
    protected $casts = [
        'bullets' => 'array',
        'created_at' => 'datetime',
        'updated_at' => 'datetime',
    ];

    /**
     * Получить единственную запись страницы "О нас" (singleton)
     * 
     * @return AboutPage
     */
    public static function getPage(): self
    {
        $page = self::first();
        
        if (!$page) {
            // Создаем страницу по умолчанию
            $page = self::create([
                'title' => 'СВОЙ ХЛЕБ',
                'phone' => '+7 982 682-43-68',
                'address' => 'поселок Исток, ул. Главная, дом 15',
                'description' => "Представляем вашему вниманию компанию «СВОЙ ХЛЕБ».\nМы доставляем горячие блюда по всему городу: кейтеринг, накроем ваш стол от десертов до горячих блюд.\nПриятно удивим вас качеством нашей продукции.",
                'bullets' => [
                    'Минимальный заказ от 3000 руб. любого наименования.',
                    'Бесплатная доставка от 10 000 руб.',
                    'Также возможен самовывоз из нашего магазина: поселок Исток, ул. Главная, дом 15.',
                ],
                'yandex_maps_url' => null,
                'cover_image_url' => null,
            ]);
        }
        
        return $page;
    }
}
