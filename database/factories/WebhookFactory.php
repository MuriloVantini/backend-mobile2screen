<?php

namespace Database\Factories;

use App\Models\User;
use App\Models\Webhook;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Str;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\Webhook>
 */
class WebhookFactory extends Factory
{
    protected $model = Webhook::class;

    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'user_id' => User::factory(),
            'name' => 'Webhook ' . fake()->words(2, true),
            'url' => fake()->url(),
            'secret' => Str::random(32),
            'events' => fake()->randomElements(['alert.sent', 'device.offline', 'device.online'], fake()->numberBetween(1, 3)),
            'is_active' => fake()->boolean(90),
            'last_triggered' => fake()->optional()->dateTimeBetween('-7 days', 'now'),
        ];
    }
}
