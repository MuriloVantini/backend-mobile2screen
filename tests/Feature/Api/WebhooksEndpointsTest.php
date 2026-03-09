<?php

use App\Models\Webhook;
use App\Models\WebhookLog;

test('endpoints de webhooks: index store show update logs teste e destroy', function () {
    $user = actingAsUser();

    $webhook = Webhook::factory()->for($user)->create();
    WebhookLog::factory()->for($webhook)->count(2)->create();

    $this->getJson('/api/webhooks')
        ->assertOk();
    $created = $this->postJson('/api/webhooks', [
        'name' => 'Webhook Alertas',
        'url' => 'https://example.com/webhook',
        'events' => ['alert.sent', 'alert.delivered'],
        'is_active' => true,
    ])
        ->assertCreated()
        ->assertJsonPath('data.name', 'Webhook Alertas');

    $this->getJson('/api/webhooks/' . $webhook->id)
        ->assertOk()
        ->assertJsonPath('data.id', $webhook->id);

    $this->putJson('/api/webhooks/' . $webhook->id, [
        'name' => 'Webhook Atualizado',
    ])
        ->assertOk()
        ->assertJsonPath('data.name', 'Webhook Atualizado');

    $this->getJson('/api/webhooks/' . $webhook->id . '/logs')
        ->assertOk()
        ->assertJsonStructure(['data' => ['data', 'current_page']]);

    $this->postJson('/api/webhooks/' . $webhook->id . '/test')
        ->assertOk();
    $this->deleteJson('/api/webhooks/' . $created->json('data.id'))
        ->assertOk();
});
