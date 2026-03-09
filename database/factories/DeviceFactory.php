<?php

namespace Database\Factories;

use App\Models\Device;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Str;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\Device>
 */
class DeviceFactory extends Factory
{
    protected $model = Device::class;

    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'user_id' => User::factory(),
            'name' => 'Display ' . fake()->unique()->numerify('##'),
            'type' => fake()->randomElement(['tv', 'rpi']),
            'location' => 'Setor ' . fake()->numerify('##'),
            'ip_address' => fake()->localIpv4(),
            'mac_address' => fake()->macAddress(),
            'is_online' => fake()->boolean(80),
            'last_seen' => now(),
            'connection_token' => hash('sha256', Str::uuid()->toString() . Str::random(20)),
            'metadata' => [
                'resolution' => fake()->randomElement(['1920x1080', '1366x768', '3840x2160']),
                'firmware' => fake()->semver(),
            ],
        ];
    }
}
