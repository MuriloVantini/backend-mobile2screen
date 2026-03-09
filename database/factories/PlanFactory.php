<?php

namespace Database\Factories;

use App\Models\Plan;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\Plan>
 */
class PlanFactory extends Factory
{
    protected $model = Plan::class;

    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'name' => fake()->unique()->lexify('plan-????'),
            'max_devices' => fake()->numberBetween(1, 200),
            'max_alerts_per_month' => fake()->optional()->numberBetween(100, 5000),
            'features' => [
                'api_access' => fake()->boolean(),
                'webhooks' => fake()->boolean(),
                'support' => fake()->randomElement(['community', 'email', 'priority']),
            ],
            'price' => fake()->randomFloat(2, 0, 999),
        ];
    }

    public function pro(): static
    {
        return $this->state(fn () => [
            'name' => 'pro',
            'max_devices' => 20,
            'max_alerts_per_month' => 1000,
            'features' => [
                'api_access' => true,
                'webhooks' => true,
                'support' => 'email',
                'custom_branding' => false,
            ],
            'price' => 49.90,
        ]);
    }
}
