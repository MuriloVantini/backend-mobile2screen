<?php

use App\Models\Alert;
use App\Models\AlertDelivery;
use App\Models\ApiKey;
use App\Models\Device;
use App\Models\Plan;
use App\Models\StatisticDaily;
use App\Models\Tag;
use App\Models\Webhook;

function assertUserResourceContract(array $user): void
{
    expect($user)->toHaveKeys([
        'id',
        'name',
        'email',
        'company',
        'phone',
        'plan_id',
        'status',
        'role',
        'last_active',
        'joined_at',
        'created_at',
        'updated_at',
        'deleted_at',
    ]);
}

test('contrato de resposta do login contem user resource padronizado', function () {
    $user = createUser([
        'email' => 'contrato-login@example.com',
        'password' => 'password',
    ]);

    $response = $this->postJson('/api/login', [
        'email' => $user->email,
        'password' => 'password',
    ])->assertOk();

    $payload = $response->json();

    expect($payload)->toHaveKeys(['message', 'user', 'token', 'token_type']);
    assertUserResourceContract($payload['user']);
});

test('contrato de resposta de planos usa plan resource', function () {
    $plan = Plan::query()->firstOrFail();

    $index = $this->getJson('/api/plans')->assertOk()->json();

    expect($index)->toHaveKey('data');
    expect($index['data'][0])->toHaveKeys([
        'id', 'name', 'max_devices', 'max_alerts_per_month', 'features', 'price', 'created_at',
    ]);

    $show = $this->getJson('/api/plans/' . $plan->id)->assertOk()->json('data');

    expect($show)->toHaveKeys([
        'id', 'name', 'max_devices', 'max_alerts_per_month', 'features', 'price', 'created_at',
    ]);
});

test('contrato de resposta de usuario autenticado usa user resource', function () {
    $plan = Plan::query()->firstOrFail();
    actingAsUser(['plan_id' => $plan->id]);

    $user = $this->getJson('/api/user')->assertOk()->json('data');

    assertUserResourceContract($user);
    expect($user['plan'])->toHaveKeys([
        'id', 'name', 'max_devices', 'max_alerts_per_month', 'features', 'price', 'created_at',
    ]);
});

test('contrato de resposta de tags usa tag resource', function () {
    $user = actingAsUser();
    $tag = Tag::factory()->for($user)->create();

    $data = $this->getJson('/api/tags/' . $tag->id)->assertOk()->json('data');

    expect($data)->toHaveKeys([
        'id', 'user_id', 'name', 'color', 'created_at',
    ]);
});

test('contrato de resposta de devices usa device resource', function () {
    $user = actingAsUser();
    $device = Device::factory()->for($user)->create();

    $data = $this->getJson('/api/devices/' . $device->id)->assertOk()->json('data');

    expect($data)->toHaveKeys([
        'id',
        'user_id',
        'name',
        'type',
        'location',
        'ip_address',
        'mac_address',
        'is_online',
        'last_seen',
        'connection_token',
        'metadata',
        'created_at',
        'updated_at',
        'deleted_at',
    ]);
});

test('contrato de resposta de alertas usa alert e delivery resources', function () {
    $user = actingAsUser();
    $tag = Tag::factory()->for($user)->create();
    $device = Device::factory()->for($user)->create(['is_online' => true]);
    $device->tags()->attach($tag->id);

    $alert = Alert::factory()->for($user)->create();
    $alert->tags()->attach($tag->id);
    AlertDelivery::factory()->create([
        'alert_id' => $alert->id,
        'device_id' => $device->id,
        'status' => 'pending',
    ]);

    $show = $this->getJson('/api/alerts/' . $alert->id)->assertOk()->json('data.alert');
    expect($show)->toHaveKeys([
        'id', 'user_id', 'title', 'message', 'type', 'duration_seconds', 'priority', 'sent_at', 'expires_at', 'created_at',
    ]);

    $deliveries = $this->getJson('/api/alerts/' . $alert->id . '/deliveries')->assertOk()->json('data');
    expect($deliveries[0])->toHaveKeys([
        'id',
        'alert_id',
        'device_id',
        'status',
        'delivered_at',
        'acknowledged_at',
        'dismissed_at',
        'error_message',
        'retry_count',
        'created_at',
    ]);
});

test('contrato de resposta de api keys nao expoe key_hash', function () {
    $user = actingAsUser();
    ApiKey::factory()->for($user)->create();

    $keys = $this->getJson('/api/api-keys')->assertOk()->json('data');

    expect($keys[0])->toHaveKeys([
        'id', 'user_id', 'name', 'last_used', 'expires_at', 'is_active', 'created_at',
    ]);
    expect($keys[0])->not->toHaveKey('key_hash');
});

test('contrato de resposta de webhooks usa webhook resource', function () {
    $user = actingAsUser();
    $webhook = Webhook::factory()->for($user)->create();

    $data = $this->getJson('/api/webhooks/' . $webhook->id)->assertOk()->json('data');

    expect($data)->toHaveKeys([
        'id',
        'user_id',
        'name',
        'url',
        'secret',
        'events',
        'is_active',
        'last_triggered',
        'created_at',
        'updated_at',
        'logs_count',
    ]);
});

test('contrato de resposta de estatisticas diarias usa statistic daily resource', function () {
    $user = actingAsUser();

    StatisticDaily::factory()->for($user)->create([
        'date' => now()->subDay()->toDateString(),
    ]);

    $data = $this->getJson('/api/statistics/daily?days=7')->assertOk()->json('data');

    expect($data[0])->toHaveKeys([
        'id',
        'user_id',
        'date',
        'alerts_sent',
        'alerts_delivered',
        'alerts_failed',
        'devices_online_avg',
        'delivery_rate',
        'created_at',
    ]);
});
