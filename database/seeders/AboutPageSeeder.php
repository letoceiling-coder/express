<?php

namespace Database\Seeders;

use App\Models\AboutPage;
use Illuminate\Database\Seeder;

class AboutPageSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        // Проверяем, существует ли уже запись
        $existing = AboutPage::first();
        
        if (!$existing) {
            AboutPage::create([
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
    }
}
