<?php

use App\Models\Device;
use App\Models\Tag;

test('devices endpoints: index latest store show update destroy heartbeat and regenerate token', function () {
    $user = actingAsUser();
    $tag = Tag::factory()->for($user)->create();
    $device = Device::factory()->for($user)->create(['name' => 'Painel A']);
    $device->tags()->attach($tag->id);

    Device::factory()->count(6)->for($user)->create();

    $this->getJson('/api/devices')
        ->assertOk()
        ->assertJsonPath('success', true);

    $this->getJson('/api/devices/latest')
        ->assertOk()
        ->assertJsonPath('success', true)
        ->assertJsonCount(5, 'data');

    $created = $this->postJson('/api/devices', [
        'name' => 'Display Novo',
        'type' => 'tv',
        'location' => 'Recepcao',
        'ip_address' => '10.0.0.1',
        'tags' => [$tag->id],
    ])
        ->assertCreated()
        ->assertJsonPath('success', true)
        ->assertJsonPath('data.name', 'Display Novo');

    $deviceId = $created->json('data.id');

    $this->getJson('/api/devices/' . $device->id)
        ->assertOk()
        ->assertJsonPath('success', true)
        ->assertJsonPath('data.id', $device->id);

    $this->putJson('/api/devices/' . $device->id, [
        'name' => 'Painel Atualizado',
        'type' => 'rpi',
    ])
        ->assertOk()
        ->assertJsonPath('data.name', 'Painel Atualizado')
        ->assertJsonPath('data.type', 'rpi');

    $this->postJson('/api/devices/' . $device->id . '/heartbeat', [
        'ip_address' => '10.0.0.99',
        'metadata' => ['firmware' => '1.0.1'],
    ])
        ->assertOk()
        ->assertJsonPath('success', true);

    $oldToken = Device::query()->findOrFail($device->id)->connection_token;

    $this->postJson('/api/devices/' . $device->id . '/regenerate-token')
        ->assertOk()
        ->assertJsonPath('success', true);

    $newToken = Device::query()->findOrFail($device->id)->connection_token;
    expect($newToken)->not->toBe($oldToken);

    $this->deleteJson('/api/devices/' . $deviceId)
        ->assertOk()
        ->assertJsonPath('success', true);
});

test('devices endpoints enforce ownership where expected', function () {
    $owner = createUser();
    $outsider = actingAsUser();

    $device = Device::factory()->for($owner)->create();

    $this->getJson('/api/devices/' . $device->id)->assertForbidden();
    $this->putJson('/api/devices/' . $device->id, ['name' => 'X'])->assertForbidden();
    $this->deleteJson('/api/devices/' . $device->id)->assertForbidden();
    $this->postJson('/api/devices/' . $device->id . '/regenerate-token')->assertForbidden();

    expect($outsider->id)->not->toBe($owner->id);
});
