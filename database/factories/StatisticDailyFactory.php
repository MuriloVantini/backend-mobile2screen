<?php

namespace Database\Factories;

use App\Models\StatisticDaily;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\StatisticDaily>
 */
class StatisticDailyFactory extends Factory
{
    protected $model = StatisticDaily::class;

    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        $alertsSent = fake()->numberBetween(1, 200);
        $alertsDelivered = fake()->numberBetween(0, $alertsSent);
        $alertsFailed = $alertsSent - $alertsDelivered;

        return [
            'user_id' => User::factory(),
            'date' => fake()->dateTimeBetween('-30 days', 'now')->format('Y-m-d'),
            'alerts_sent' => $alertsSent,
            'alerts_delivered' => $alertsDelivered,
            'alerts_failed' => $alertsFailed,
            'devices_online_avg' => fake()->randomFloat(2, 0, 50),
            'delivery_rate' => $alertsSent > 0
                ? round(($alertsDelivered / $alertsSent) * 100, 2)
                : 0,
        ];
    }
}
