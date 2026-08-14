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

test('simulador kiosk autentica somente o proprio canal privado do Reverb', function () {
    config()->set('broadcasting.connections.reverb.key', 'reverb-test-key');
    config()->set('broadcasting.connections.reverb.secret', 'reverb-test-secret');

    $device = Device::factory()->for(createUser())->create([
        'connection_token' => 'token-do-dispositivo',
    ]);
    $headers = ['X-Device-Token' => 'token-do-dispositivo'];
    $socketId = '1234.5678';
    $channel = 'private-device.' . $device->id;
    $expectedSignature = hash_hmac('sha256', "{$socketId}:{$channel}", 'reverb-test-secret');

    $this->postJson('/api/kiosk/devices/' . $device->id . '/broadcasting/auth', [
        'socket_id' => $socketId,
        'channel_name' => $channel,
    ], $headers)
        ->assertOk()
        ->assertJsonPath('auth', 'reverb-test-key:' . $expectedSignature);

    $this->postJson('/api/kiosk/devices/' . $device->id . '/broadcasting/auth', [
        'socket_id' => $socketId,
        'channel_name' => 'private-device.999999',
    ], $headers)->assertForbidden();
});
