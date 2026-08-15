<?php

namespace App\Services;

use App\Jobs\DeliverWebhook;
use App\Models\Webhook;
use Illuminate\Support\Str;

class WebhookDispatcher
{
    /**
     * Enfileira uma entrega para cada webhook ativo do usuário interessado no evento.
     *
     * @param  array<string, mixed>  $data
     */
    public function dispatch(int $userId, string $eventType, array $data): void
    {
        Webhook::query()
            ->where('user_id', $userId)
            ->where('is_active', true)
            ->whereJsonContains('events', $eventType)
            ->each(function (Webhook $webhook) use ($eventType, $data) {
                DeliverWebhook::dispatch(
                    $webhook->id,
                    [
                        'id' => (string) Str::uuid(),
                        'event' => $eventType,
                        'occurred_at' => now()->toIso8601String(),
                        'data' => $data,
                    ],
                )->afterCommit();
            });
    }
}
