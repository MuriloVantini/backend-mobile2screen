<?php

use App\Events\SystemStateChanged;
use App\Models\Device;
use Illuminate\Support\Facades\Event;

test('usuario autentica apenas o proprio canal privado', function () {
    $user = actingAsUser();

    $this->postJson('/api/realtime/authorize', [
        'socket_id' => '123.456',
        'channel_name' => 'private-user.'.$user->id,
    ])->assertOk()->assertJsonStructure(['auth']);

    $this->postJson('/api/realtime/authorize', [
        'socket_id' => '123.456',
        'channel_name' => 'private-user.'.($user->id + 1),
    ])->assertForbidden();

    $this->postJson('/api/realtime/authorize', [
        'socket_id' => '123.456',
        'channel_name' => 'private-admin-dashboard',
    ])->assertForbidden();
});

test('administrador autentica o canal do painel administrativo', function () {
    actingAsUser(['role' => 'admin']);

    $this->postJson('/api/realtime/authorize', [
        'socket_id' => '321.654',
        'channel_name' => 'private-admin-dashboard',
    ])->assertOk()->assertJsonStructure(['auth']);
});

test('mudanca de presenca publica atualizacao em tempo real', function () {
    Event::fake([SystemStateChanged::class]);
    $user = createUser();
    $device = Device::factory()->for($user)->create(['is_online' => false]);

    $this->withHeader('X-Device-Token', $device->connection_token)
        ->postJson('/api/kiosk/devices/'.$device->id.'/connect')
        ->assertOk();

    Event::assertDispatched(SystemStateChanged::class, fn (SystemStateChanged $event) => $event->userId === $user->id && $event->resource === 'devices'
    );
});
