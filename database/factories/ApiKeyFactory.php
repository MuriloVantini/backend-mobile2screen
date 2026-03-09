<?php

namespace Database\Factories;

use App\Models\ApiKey;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Str;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\ApiKey>
 */
class ApiKeyFactory extends Factory
{
    protected $model = ApiKey::class;

    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'user_id' => User::factory(),
            'key_hash' => hash('sha256', Str::uuid()->toString() . Str::random(20)),
            'name' => 'API Key ' . fake()->words(2, true),
            'last_used' => fake()->optional()->dateTimeBetween('-10 days', 'now'),
            'expires_at' => fake()->optional()->dateTimeBetween('+1 month', '+1 year'),
            'is_active' => fake()->boolean(90),
        ];
    }
}
