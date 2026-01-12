<?php

namespace Database\Factories;

use App\Models\OrderStatusHistory;
use App\Models\Order;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\OrderStatusHistory>
 */
class OrderStatusHistoryFactory extends Factory
{
    protected $model = OrderStatusHistory::class;

    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'order_id' => Order::factory(),
            'status' => $this->faker->randomElement([
                \App\Models\Order::STATUS_NEW,
                \App\Models\Order::STATUS_ACCEPTED,
                \App\Models\Order::STATUS_SENT_TO_KITCHEN,
                \App\Models\Order::STATUS_KITCHEN_ACCEPTED,
                \App\Models\Order::STATUS_PREPARING,
                \App\Models\Order::STATUS_READY_FOR_DELIVERY,
                \App\Models\Order::STATUS_COURIER_ASSIGNED,
                \App\Models\Order::STATUS_IN_TRANSIT,
                \App\Models\Order::STATUS_DELIVERED,
            ]),
            'previous_status' => $this->faker->optional()->randomElement([
                \App\Models\Order::STATUS_NEW,
                \App\Models\Order::STATUS_ACCEPTED,
            ]),
            'role' => $this->faker->randomElement(['admin', 'kitchen', 'courier', 'user']),
            'comment' => $this->faker->optional()->sentence(),
            'metadata' => $this->faker->optional()->randomElement([
                null,
                ['courier_id' => 1],
                ['estimated_time' => '30 минут'],
            ]),
        ];
    }
}





