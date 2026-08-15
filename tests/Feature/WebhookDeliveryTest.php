<?php

use App\Jobs\DeliverWebhook;
use App\Models\Device;
use App\Models\Tag;
use App\Models\Webhook;
use App\Services\WebhookDispatcher;
use Illuminate\Http\Client\Request;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Queue;

test('entrega webhook envia post assinado e registra o resultado', function () {
    Http::fake(['https://example.com/hook' => Http::response(['received' => true], 202)]);
    $user = actingAsUser();
    $webhook = Webhook::factory()->for($user)->create([
        'url' => 'https://example.com/hook',
        'secret' => 'segredo-super-seguro',
        'events' => ['alert.sent'],
        'is_active' => true,
    ]);
    $payload = [
        'id' => 'delivery-123',
        'event' => 'alert.sent',
        'occurred_at' => now()->toIso8601String(),
        'data' => ['alert_id' => 10],
    ];

    DeliverWebhook::dispatchSync($webhook->id, $payload);

    Http::assertSent(function (Request $request) use ($payload) {
        $body = json_encode($payload, JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE | JSON_THROW_ON_ERROR);

        return $request->method() === 'POST'
            && $request->url() === 'https://example.com/hook'
            && $request->header('X-Mobile2Screen-Event')[0] === 'alert.sent'
            && $request->header('X-Mobile2Screen-Delivery')[0] === 'delivery-123'
            && $request->header('X-Mobile2Screen-Signature')[0] === 'sha256='.hash_hmac('sha256', $body, 'segredo-super-seguro');
    });

    $this->assertDatabaseHas('webhook_logs', [
        'webhook_id' => $webhook->id,
        'event_type' => 'alert.sent',
        'response_status' => 202,
        'error_message' => null,
    ]);
    expect($webhook->fresh()->last_triggered)->not->toBeNull();
});

test('dispatcher enfileira apenas webhooks ativos interessados no evento', function () {
    Queue::fake();
    $user = actingAsUser();
    $matching = Webhook::factory()->for($user)->create(['events' => ['device.offline'], 'is_active' => true]);
    Webhook::factory()->for($matching->user)->create(['events' => ['alert.sent'], 'is_active' => true]);
    Webhook::factory()->for($matching->user)->create(['events' => ['device.offline'], 'is_active' => false]);

    app(WebhookDispatcher::class)->dispatch($matching->user_id, 'device.offline', ['device_id' => 5]);

    Queue::assertPushed(DeliverWebhook::class, 1);
    Queue::assertPushed(fn (DeliverWebhook $job) => $job->webhookId === $matching->id
        && $job->payload['event'] === 'device.offline'
        && $job->payload['data']['device_id'] === 5);
});

test('job configura tres tentativas com intervalos progressivos', function () {
    $job = new DeliverWebhook(1, [
        'id' => 'delivery-123',
        'event' => 'alert.sent',
        'occurred_at' => now()->toIso8601String(),
        'data' => [],
    ]);

    expect($job->tries)->toBe(3)
        ->and($job->backoff())->toBe([10, 60]);
});

test('criacao de alerta dispara eventos de envio e falha selecionados', function () {
    Queue::fake();
    $user = actingAsUser();
    Webhook::factory()->for($user)->create([
        'events' => ['alert.sent', 'alert.failed'],
        'is_active' => true,
    ]);
    $tag = Tag::factory()->for($user)->create();
    $online = Device::factory()->for($user)->create(['is_online' => true]);
    $offline = Device::factory()->for($user)->create(['is_online' => false]);
    $online->tags()->attach($tag->id);
    $offline->tags()->attach($tag->id);

    $this->postJson('/api/alerts', [
        'title' => 'Teste de webhook',
        'message' => 'Evento integrado',
        'type' => 'info',
        'tags' => [$tag->id],
    ])->assertCreated();

    Queue::assertPushed(DeliverWebhook::class, 2);
    Queue::assertPushed(fn (DeliverWebhook $job) => $job->payload['event'] === 'alert.sent');
    Queue::assertPushed(fn (DeliverWebhook $job) => $job->payload['event'] === 'alert.failed'
        && $job->payload['data']['failed_devices'] === 1);
});

test('comando marca dispositivo sem heartbeat como offline e dispara evento', function () {
    Queue::fake();
    $user = actingAsUser();
    Webhook::factory()->for($user)->create(['events' => ['device.offline'], 'is_active' => true]);
    $device = Device::factory()->for($user)->create([
        'is_online' => true,
        'last_seen' => now()->subMinutes(5),
    ]);

    $this->artisan('devices:mark-offline --minutes=2')->assertSuccessful();

    expect($device->fresh()->is_online)->toBeFalse();
    Queue::assertPushed(fn (DeliverWebhook $job) => $job->payload['event'] === 'device.offline'
        && $job->payload['data']['device_id'] === $device->id);
});
