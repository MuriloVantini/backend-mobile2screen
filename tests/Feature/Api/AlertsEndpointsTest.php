<?php

use App\Events\AlertAvailable;
use App\Models\Alert;
use App\Models\AlertDelivery;
use App\Models\Device;
use App\Models\Tag;
use Illuminate\Support\Facades\Event;

test('endpoints de alertas: index store show entregas retry e update de status de entrega', function () {
    Event::fake([AlertAvailable::class]);
    $user = actingAsUser();

    $tag = Tag::factory()->for($user)->create();
    $onlineDevice = Device::factory()->for($user)->create(['is_online' => true]);
    $offlineDevice = Device::factory()->for($user)->create(['is_online' => false]);

    $onlineDevice->tags()->attach($tag->id);
    $offlineDevice->tags()->attach($tag->id);

    $this->getJson('/api/alerts')
        ->assertOk();
    $storeResponse = $this->postJson('/api/alerts', [
        'title' => 'Alerta Critico',
        'message' => 'Mensagem de teste',
        'type' => 'critical',
        'priority' => 1,
        'tags' => [$tag->id],
    ])
        ->assertCreated()
        ->assertJsonPath('data.devices_count', 2);

    $alertId = $storeResponse->json('data.alert.id');

    Event::assertDispatched(AlertAvailable::class, fn (AlertAvailable $event) =>
        $event->delivery->alert_id === $alertId
        && $event->delivery->device_id === $onlineDevice->id
    );

    $this->getJson('/api/alerts/' . $alertId)
        ->assertOk()
        ->assertJsonPath('data.alert.id', $alertId);

    $this->getJson('/api/alerts/' . $alertId . '/deliveries')
        ->assertOk()
        ->assertJsonCount(2, 'data');

    $delivery = AlertDelivery::query()
        ->where('alert_id', $alertId)
        ->where('device_id', $onlineDevice->id)
        ->firstOrFail();

    $this->patchJson('/api/deliveries/' . $delivery->id . '/status', [
        'status' => 'delivered',
    ])
        ->assertOk();
    AlertDelivery::query()
        ->where('alert_id', $alertId)
        ->where('status', 'failed')
        ->update(['status' => 'failed', 'retry_count' => 0]);

    Event::fake([AlertAvailable::class]);

    $this->postJson('/api/alerts/' . $alertId . '/retry')
        ->assertOk()
        ->assertJsonPath('data.retried_devices', 1)
        ->assertJsonPath('data.offline_devices', 1);

    expect($delivery->fresh()->status)->toBe('pending');
    Event::assertDispatched(AlertAvailable::class, fn (AlertAvailable $event) =>
        $event->delivery->id === $delivery->id
        && $event->delivery->device_id === $onlineDevice->id
    );
});

test('retry reenvia alerta ja recebido para o dispositivo online', function () {
    Event::fake([AlertAvailable::class]);
    $user = actingAsUser();
    $alert = Alert::factory()->for($user)->create();
    $device = Device::factory()->for($user)->create(['is_online' => true]);
    $delivery = AlertDelivery::factory()->for($alert)->for($device)->create([
        'status' => 'dismissed',
        'delivered_at' => now()->subMinute(),
        'acknowledged_at' => now()->subSeconds(40),
        'dismissed_at' => now()->subSeconds(30),
        'retry_count' => 0,
    ]);

    $this->postJson('/api/alerts/' . $alert->id . '/retry')
        ->assertOk()
        ->assertJsonPath('data.retried_devices', 1)
        ->assertJsonPath('data.offline_devices', 0);

    $delivery->refresh();
    expect($delivery->status)->toBe('pending')
        ->and($delivery->retry_count)->toBe(1)
        ->and($delivery->acknowledged_at)->toBeNull()
        ->and($delivery->dismissed_at)->toBeNull()
        ->and($delivery->delivered_at)->not->toBeNull();

    Event::assertDispatched(AlertAvailable::class, fn (AlertAvailable $event) =>
        $event->delivery->id === $delivery->id
    );
});

test('historico retorna a quantidade de dispositivos que receberam cada alerta', function () {
    $user = actingAsUser();
    $alert = Alert::factory()->for($user)->create();
    $receivedDevice = Device::factory()->for($user)->create();
    $failedDevice = Device::factory()->for($user)->create();
    $pendingDevice = Device::factory()->for($user)->create();

    AlertDelivery::factory()->for($alert)->for($receivedDevice)->create([
        'status' => 'dismissed',
        'delivered_at' => now(),
        'dismissed_at' => now(),
    ]);
    AlertDelivery::factory()->for($alert)->for($failedDevice)->create([
        'status' => 'failed',
        'delivered_at' => null,
    ]);
    AlertDelivery::factory()->for($alert)->for($pendingDevice)->create([
        'status' => 'pending',
        'delivered_at' => null,
    ]);

    $this->getJson('/api/alerts')
        ->assertOk()
        ->assertJsonPath('data.data.0.devices_count', 3)
        ->assertJsonPath('data.data.0.received_devices_count', 1)
        ->assertJsonPath('data.data.0.failed_devices_count', 1)
        ->assertJsonPath('data.data.0.pending_devices_count', 1);
});
