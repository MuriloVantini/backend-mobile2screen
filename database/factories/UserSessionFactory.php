<?php

namespace Database\Factories;

use App\Models\User;
use App\Models\UserSession;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Str;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\UserSession>
 */
class UserSessionFactory extends Factory
{
    protected $model = UserSession::class;

    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'user_id' => User::factory(),
            'token' => hash('sha256', Str::uuid()->toString() . Str::random(20)),
            'refresh_token' => hash('sha256', Str::uuid()->toString() . Str::random(20)),
            'ip_address' => fake()->localIpv4(),
            'user_agent' => fake()->userAgent(),
            'expires_at' => fake()->dateTimeBetween('+1 day', '+30 days'),
        ];
    }
}
