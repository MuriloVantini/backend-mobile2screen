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
