<?php

namespace Database\Seeders;

use App\Models\Banner;
use Illuminate\Database\Seeder;

class BannerSeeder extends Seeder
{
    /**
     * Run the database seeds.
     * Добавляет баннеры для HeroSlider на главной странице.
     */
    public function run(): void
    {
        if (Banner::exists()) {
            return;
        }

        $banners = [
            [
                'title' => 'Свежая выпечка каждый день',
                'subtitle' => 'Печём с душой из отборной муки',
                'image' => null,
                'cta_text' => 'В каталог',
                'cta_href' => '/#products',
                'is_active' => true,
                'sort_order' => 0,
            ],
            [
                'title' => 'Быстрая доставка',
                'subtitle' => 'Доставим за 1–2 часа по городу',
                'image' => null,
                'cta_text' => 'Заказать',
                'cta_href' => '/cart',
                'is_active' => true,
                'sort_order' => 1,
            ],
            [
                'title' => 'Акции и скидки',
                'subtitle' => 'Специальные предложения для вас',
                'image' => null,
                'cta_text' => 'Смотреть',
                'cta_href' => '/#products',
                'is_active' => true,
                'sort_order' => 2,
            ],
        ];

        foreach ($banners as $data) {
            Banner::create($data);
        }
    }
}
