<?php

namespace Database\Factories;

use App\Models\Alert;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\Alert>
 */
class AlertFactory extends Factory
{
    protected $model = Alert::class;

    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'user_id' => User::factory(),
            'title' => fake()->unique()->sentence(3),
            'message' => fake()->paragraph(),
            'type' => fake()->randomElement(['info', 'warning', 'critical', 'success']),
            'duration_seconds' => fake()->optional()->numberBetween(10, 120),
            'priority' => fake()->numberBetween(0, 3),
            'sent_at' => now(),
            'expires_at' => fake()->optional()->dateTimeBetween('+1 day', '+7 days'),
        ];
    }
}
