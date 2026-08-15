<?php

use App\Models\Webhook;
use App\Models\WebhookLog;
use Illuminate\Support\Facades\Http;

test('endpoints de webhooks: index store show update logs teste e destroy', function () {
    Http::fake(['*' => Http::response(['received' => true], 200)]);
    $user = actingAsUser();

    $webhook = Webhook::factory()->for($user)->create();
    WebhookLog::factory()->for($webhook)->count(2)->create();

    $this->getJson('/api/webhooks')
        ->assertOk();
    $created = $this->postJson('/api/webhooks', [
        'name' => 'Webhook Alertas',
        'url' => 'https://example.com/webhook',
        'secret' => 'segredo-de-teste-com-32-caracteres',
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
        ->assertOk()
        ->assertJsonPath('message', 'Webhook de teste enviado com sucesso');
    expect($webhook->logs()->where('event_type', 'webhook.test')->exists())->toBeTrue();
    $this->deleteJson('/api/webhooks/' . $created->json('data.id'))
        ->assertOk();
});

test('teste de webhook registra e informa falha de conectividade', function () {
    Http::fake(['*' => Http::response(['error' => true], 503)]);
    $user = actingAsUser();
    $webhook = Webhook::factory()->for($user)->create();

    $this->postJson('/api/webhooks/' . $webhook->id . '/test')
        ->assertUnprocessable()
        ->assertJsonPath('message', 'A URL do webhook não respondeu com sucesso.');

    $this->assertDatabaseHas('webhook_logs', [
        'webhook_id' => $webhook->id,
        'event_type' => 'webhook.test',
        'response_status' => 503,
    ]);
});

test('cadastro rejeita endpoint inacessivel', function () {
    Http::fake(['*' => Http::response(['error' => true], 500)]);
    $user = actingAsUser();

    $this->postJson('/api/webhooks', [
        'name' => 'Endpoint indisponivel',
        'url' => 'https://example.com/offline',
        'secret' => 'segredo-de-teste-com-32-caracteres',
        'events' => ['alert.sent'],
        'is_active' => true,
    ])
        ->assertUnprocessable()
        ->assertJsonPath('message', 'A URL do webhook não respondeu com sucesso. O cadastro não foi realizado.');

    expect($user->webhooks()->where('name', 'Endpoint indisponivel')->exists())->toBeFalse();
});

test('cadastro rejeita endereco de rede privada', function () {
    actingAsUser();

    $this->postJson('/api/webhooks', [
        'name' => 'Endpoint interno',
        'url' => 'http://127.0.0.1/internal',
        'secret' => 'segredo-de-teste-com-32-caracteres',
        'events' => ['alert.sent'],
        'is_active' => true,
    ])
        ->assertUnprocessable()
        ->assertJsonValidationErrors('url');
});
