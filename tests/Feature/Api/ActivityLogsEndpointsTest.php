<?php

use App\Models\ActivityLog;
use App\Models\Device;

test('mutacoes autenticadas geram registro de auditoria', function () {
    $user = actingAsUser();

    $this->postJson('/api/devices', ['name' => 'Auditado', 'type' => 'tv'])->assertCreated();

    expect(ActivityLog::query()
        ->where('user_id', $user->id)
        ->where('action', 'device.store')
        ->exists())->toBeTrue();
});

test('administrador filtra logs e usuario comum nao acessa auditoria', function () {
    $owner = createUser();
    $device = Device::factory()->for($owner)->create();
    ActivityLog::factory()->for($owner)->create([
        'action' => 'device.update',
        'resource_type' => 'device',
        'resource_id' => $device->id,
    ]);

    actingAsUser();
    $this->getJson('/api/activity-logs')->assertForbidden();

    actingAsUser(['role' => 'admin']);
    $this->getJson('/api/activity-logs?user_id='.$owner->id.'&action=device')
        ->assertOk()
        ->assertJsonPath('data.data.0.action', 'device.update')
        ->assertJsonPath('data.data.0.user.id', $owner->id);
});
