<?php

use App\Models\Webhook;
use App\Models\WebhookLog;

test('webhooks endpoints: index store show update logs test and destroy', function () {
    $user = actingAsUser();

    $webhook = Webhook::factory()->for($user)->create();
    WebhookLog::factory()->for($webhook)->count(2)->create();

    $this->getJson('/api/webhooks')
        ->assertOk()
        ->assertJsonPath('success', true);

    $created = $this->postJson('/api/webhooks', [
        'name' => 'Webhook Alertas',
        'url' => 'https://example.com/webhook',
        'events' => ['alert.sent', 'alert.delivered'],
        'is_active' => true,
    ])
        ->assertCreated()
        ->assertJsonPath('success', true)
        ->assertJsonPath('data.name', 'Webhook Alertas');

    $this->getJson('/api/webhooks/' . $webhook->id)
        ->assertOk()
        ->assertJsonPath('success', true)
        ->assertJsonPath('data.id', $webhook->id);

    $this->putJson('/api/webhooks/' . $webhook->id, [
        'name' => 'Webhook Atualizado',
    ])
        ->assertOk()
        ->assertJsonPath('success', true)
        ->assertJsonPath('data.name', 'Webhook Atualizado');

    $this->getJson('/api/webhooks/' . $webhook->id . '/logs')
        ->assertOk()
        ->assertJsonPath('success', true)
        ->assertJsonStructure(['data' => ['data', 'current_page']]);

    $this->postJson('/api/webhooks/' . $webhook->id . '/test')
        ->assertOk()
        ->assertJsonPath('success', true);

    $this->deleteJson('/api/webhooks/' . $created->json('data.id'))
        ->assertOk()
        ->assertJsonPath('success', true);
});
