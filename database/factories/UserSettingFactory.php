<?php

namespace Database\Factories;

use App\Models\User;
use App\Models\UserSetting;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\UserSetting>
 */
class UserSettingFactory extends Factory
{
    protected $model = UserSetting::class;

    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'user_id' => User::factory(),
            'notify_alert_failed' => fake()->boolean(85),
            'notify_device_offline' => fake()->boolean(85),
            'notify_weekly_report' => fake()->boolean(70),
            'notify_device_connected' => fake()->boolean(85),
            'notify_limit_reached' => fake()->boolean(85),
            'notification_email' => fake()->safeEmail(),
            'notification_phone' => '+55 11 9' . fake()->numerify('####-####'),
            'timezone' => 'America/Sao_Paulo',
            'language' => 'pt-BR',
            'theme' => fake()->randomElement(['light', 'dark', 'auto']),
        ];
    }
}
