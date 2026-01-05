<?php

namespace Database\Factories;

use App\Models\Order;
use App\Models\Bot;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\Order>
 */
class OrderFactory extends Factory
{
    protected $model = Order::class;

    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        $now = now();
        $dateStr = $now->format('Ymd');
        $randomNum = rand(1, 9999);
        $orderId = "ORD-{$dateStr}-{$randomNum}";

        return [
            'order_id' => $orderId,
            'telegram_id' => $this->faker->numberBetween(100000000, 999999999),
            'name' => $this->faker->name(),
            'status' => Order::STATUS_NEW,
            'phone' => $this->faker->phoneNumber(),
            'delivery_address' => $this->faker->address(),
            'delivery_type' => $this->faker->randomElement(['courier', 'pickup']),
            'delivery_time' => $this->faker->time('H:i'),
            'delivery_cost' => $this->faker->randomFloat(2, 0, 500),
            'comment' => $this->faker->optional()->sentence(),
            'total_amount' => $this->faker->randomFloat(2, 100, 5000),
            'payment_status' => Order::PAYMENT_STATUS_PENDING,
            'bot_id' => Bot::factory(),
        ];
    }
}

