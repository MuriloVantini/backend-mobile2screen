<?php

namespace Database\Factories;

use App\Models\ActivityLog;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\ActivityLog>
 */
class ActivityLogFactory extends Factory
{
    protected $model = ActivityLog::class;

    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'user_id' => User::factory(),
            'action' => fake()->randomElement(['login', 'alert_sent', 'device_added']),
            'resource_type' => fake()->randomElement(['user', 'device', 'alert']),
            'resource_id' => fake()->numberBetween(1, 1000),
            'ip_address' => fake()->localIpv4(),
            'user_agent' => fake()->userAgent(),
            'metadata' => ['source' => 'factory'],
        ];
    }
}
