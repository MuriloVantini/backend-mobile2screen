<?php

use App\Models\Alert;
use App\Models\AlertDelivery;
use App\Models\Device;
use App\Models\StatisticDaily;
use App\Models\Tag;

test('endpoints de estatisticas: dashboard diario alertas por tipo e principais dispositivos', function () {
    $user = actingAsUser();

    foreach (range(1, 3) as $offset) {
        StatisticDaily::factory()->for($user)->create([
            'date' => now()->subDays($offset)->toDateString(),
        ]);
    }

    $tag = Tag::factory()->for($user)->create();
    $device = Device::factory()->for($user)->create();

    $alert = Alert::factory()->for($user)->create([
        'type' => 'warning',
        'sent_at' => now()->subDay(),
    ]);

    $alert->tags()->attach($tag->id);

    AlertDelivery::factory()->create([
        'alert_id' => $alert->id,
        'device_id' => $device->id,
        'status' => 'delivered',
    ]);

    $this->getJson('/api/statistics/dashboard')
        ->assertOk()
        ->assertJsonStructure(['data' => ['devices', 'alerts', 'deliveries', 'top_tags']]);

    $this->getJson('/api/statistics/daily?days=7')
        ->assertOk();
    $this->getJson('/api/statistics/alerts-by-type?days=7')
        ->assertOk();
    $this->getJson('/api/statistics/top-devices?days=7&limit=5')
        ->assertOk();
});

test('dashboard administrativo agrega metricas reais de todo o sistema', function () {
    actingAsUser(['role' => 'admin']);
    $user = createUser(['name' => 'Cliente Dashboard']);
    $online = Device::factory()->for($user)->create(['is_online' => true]);
    $offline = Device::factory()->for($user)->create(['is_online' => false]);
    $alert = Alert::factory()->for($user)->create([
        'type' => 'critical',
        'sent_at' => now(),
    ]);
    AlertDelivery::factory()->for($alert)->for($online)->create([
        'status' => 'delivered',
        'delivered_at' => now(),
    ]);
    AlertDelivery::factory()->for($alert)->for($offline)->create([
        'status' => 'failed',
        'delivered_at' => null,
    ]);

    $this->getJson('/api/statistics/admin-dashboard')
        ->assertOk()
        ->assertJsonPath('data.users.total', 2)
        ->assertJsonPath('data.devices.total', 2)
        ->assertJsonPath('data.devices.online', 1)
        ->assertJsonPath('data.devices.offline', 1)
        ->assertJsonPath('data.alerts.today', 1)
        ->assertJsonPath('data.deliveries.total', 2)
        ->assertJsonPath('data.deliveries.delivered', 1)
        ->assertJsonPath('data.deliveries.failed', 1)
        ->assertJsonPath('data.deliveries.delivery_rate', 50)
        ->assertJsonCount(7, 'data.daily')
        ->assertJsonPath('data.alerts_by_type.0.type', 'critical')
        ->assertJsonPath('data.top_users.0.name', 'Cliente Dashboard');
});

test('dashboard administrativo rejeita usuario comum', function () {
    actingAsUser();

    $this->getJson('/api/statistics/admin-dashboard')->assertForbidden();
});
