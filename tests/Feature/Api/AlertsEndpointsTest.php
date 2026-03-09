<?php

use App\Models\AlertDelivery;
use App\Models\Device;
use App\Models\Tag;

test('endpoints de alertas: index store show entregas retry e update de status de entrega', function () {
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

    $this->getJson('/api/alerts/' . $alertId)
        ->assertOk()
        ->assertJsonPath('data.alert.id', $alertId);

    $this->getJson('/api/alerts/' . $alertId . '/deliveries')
        ->assertOk()
        ->assertJsonCount(2, 'data');

    $delivery = AlertDelivery::query()->where('alert_id', $alertId)->firstOrFail();

    $this->patchJson('/api/deliveries/' . $delivery->id . '/status', [
        'status' => 'delivered',
    ])
        ->assertOk();
    AlertDelivery::query()
        ->where('alert_id', $alertId)
        ->where('status', 'failed')
        ->update(['status' => 'failed', 'retry_count' => 0]);

    $this->postJson('/api/alerts/' . $alertId . '/retry')
        ->assertOk();
});
