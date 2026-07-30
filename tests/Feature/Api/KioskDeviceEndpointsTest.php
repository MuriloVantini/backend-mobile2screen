<?php

use App\Models\Alert;
use App\Models\AlertDelivery;
use App\Models\Device;

test('simulador kiosk conecta, recebe entregas e atualiza o status usando apenas o token do dispositivo', function () {
    $user = createUser();
    $device = Device::factory()->for($user)->create([
        'connection_token' => 'token-do-simulador',
        'is_online' => false,
    ]);
    $alert = Alert::factory()->for($user)->create(['expires_at' => now()->addHour()]);
    $delivery = AlertDelivery::factory()->for($alert)->for($device)->create(['status' => 'pending']);

    $headers = ['X-Device-Token' => 'token-do-simulador'];

    $this->postJson('/api/kiosk/devices/' . $device->id . '/connect', [], $headers)
        ->assertOk()
        ->assertJsonPath('data.device_id', $device->id);

    expect($device->fresh()->is_online)->toBeTrue();

    $this->getJson('/api/kiosk/devices/' . $device->id . '/deliveries', $headers)
        ->assertOk()
        ->assertJsonCount(1, 'data')
        ->assertJsonPath('data.0.id', $delivery->id);

    $this->patchJson('/api/kiosk/devices/' . $device->id . '/deliveries/' . $delivery->id . '/status', [
        'status' => 'delivered',
    ], $headers)->assertOk();

    expect($delivery->fresh()->status)->toBe('delivered');
    expect($delivery->fresh()->delivered_at)->not->toBeNull();
});

test('simulador kiosk rejeita token inválido', function () {
    $device = Device::factory()->for(createUser())->create([
        'connection_token' => 'token-correto',
    ]);

    $this->postJson('/api/kiosk/devices/' . $device->id . '/connect', [], [
        'X-Device-Token' => 'token-incorreto',
    ])->assertUnauthorized();
});
