<?php

namespace Database\Factories;

use App\Models\TelegramUser;
use App\Models\Bot;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\TelegramUser>
 */
class TelegramUserFactory extends Factory
{
    protected $model = TelegramUser::class;

    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'bot_id' => Bot::factory(),
            'telegram_id' => $this->faker->unique()->numberBetween(100000000, 999999999),
            'first_name' => $this->faker->firstName(),
            'last_name' => $this->faker->optional()->lastName(),
            'username' => $this->faker->optional()->userName(),
            'language_code' => $this->faker->randomElement(['ru', 'en', 'uk']),
            'is_premium' => $this->faker->boolean(20),
            'role' => TelegramUser::ROLE_USER,
            'is_blocked' => false,
        ];
    }
}





