<?php

namespace Database\Factories;

use App\Models\Bot;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\Bot>
 */
class BotFactory extends Factory
{
    protected $model = Bot::class;

    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'name' => $this->faker->name() . ' Bot',
            'username' => strtolower($this->faker->unique()->firstName()) . '_bot',
            'token' => $this->faker->uuid(),
            'is_active' => true,
            'settings' => [
                'webhook' => [
                    'url' => $this->faker->url(),
                ],
            ],
        ];
    }
}






