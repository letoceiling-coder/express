<?php

namespace Database\Seeders;

use App\Models\PaymentMethod;
use Illuminate\Database\Seeder;

class PaymentMethodSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $methods = [
            [
                'code' => 'yookassa',
                'name' => 'ЮКасса',
                'description' => 'Оплата картой через ЮКассу',
                'is_enabled' => true,
                'sort_order' => 1,
                'discount_type' => PaymentMethod::DISCOUNT_TYPE_PERCENTAGE,
                'discount_value' => 3.0, // 3% скидка
                'min_cart_amount' => 2000.0, // Минимум 2000 рублей
                'show_notification' => true,
                'notification_text' => 'При оплате через ЮКассу вы получите скидку {discount_percent}% ({discount} ₽). Итого к оплате: {final_amount} ₽',
                'settings' => [
                    'provider' => 'yookassa',
                ],
            ],
            [
                'code' => 'cash',
                'name' => 'Наличными',
                'description' => 'Оплата наличными при получении',
                'is_enabled' => true,
                'sort_order' => 2,
                'discount_type' => PaymentMethod::DISCOUNT_TYPE_NONE,
                'discount_value' => null,
                'min_cart_amount' => null,
                'show_notification' => false,
                'notification_text' => null,
                'settings' => null,
            ],
        ];

        foreach ($methods as $methodData) {
            PaymentMethod::updateOrCreate(
                ['code' => $methodData['code']],
                $methodData
            );
        }
    }
}
