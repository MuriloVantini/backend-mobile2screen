<?php

namespace Database\Factories;

use App\Models\Alert;
use App\Models\AlertDelivery;
use App\Models\Device;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\AlertDelivery>
 */
class AlertDeliveryFactory extends Factory
{
    protected $model = AlertDelivery::class;

    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        $status = fake()->randomElement(['pending', 'delivered', 'failed']);

        return [
            'alert_id' => Alert::factory(),
            'device_id' => Device::factory(),
            'status' => $status,
            'delivered_at' => $status === 'delivered' ? now() : null,
            'acknowledged_at' => $status === 'delivered' ? now()->addMinutes(2) : null,
            'dismissed_at' => null,
            'error_message' => $status === 'failed' ? 'Falha simulada na entrega' : null,
            'retry_count' => $status === 'failed' ? fake()->numberBetween(1, 3) : 0,
        ];
    }
}
