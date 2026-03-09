<?php

namespace Database\Factories;

use App\Models\Webhook;
use App\Models\WebhookLog;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\WebhookLog>
 */
class WebhookLogFactory extends Factory
{
    protected $model = WebhookLog::class;

    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'webhook_id' => Webhook::factory(),
            'event_type' => fake()->randomElement(['alert.sent', 'device.offline', 'device.online']),
            'payload' => [
                'id' => fake()->randomNumber(),
                'message' => fake()->sentence(),
            ],
            'response_status' => fake()->randomElement([200, 201, 400, 500]),
            'response_body' => '{"ok":true}',
            'error_message' => null,
        ];
    }
}
