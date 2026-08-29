<?php

use App\Events\AlertAvailable;
use App\Models\Alert;
use App\Models\Device;
use App\Models\Plan;
use App\Models\Tag;
use Illuminate\Support\Facades\Event;

test('limite mensal do plano bloqueia novo alerta com http 429', function () {
    Event::fake([AlertAvailable::class]);
    $plan = Plan::factory()->create(['max_alerts_per_month' => 1]);
    $user = actingAsUser(['plan_id' => $plan->id]);
    Alert::factory()->for($user)->create(['sent_at' => now()]);
    $tag = Tag::factory()->for($user)->create();
    $device = Device::factory()->for($user)->create(['is_online' => true]);
    $device->tags()->attach($tag->id);

    $this->postJson('/api/alerts', [
        'title' => 'Além do limite',
        'message' => 'Este alerta deve ser bloqueado',
        'type' => 'warning',
        'tags' => [$tag->id],
    ])->assertStatus(429)
        ->assertJsonPath('code', 'plan_limit_reached')
        ->assertJsonPath('resource', 'alerts')
        ->assertJsonPath('limit', 1);
});

test('limite de dispositivos do plano bloqueia novo cadastro com http 429', function () {
    $plan = Plan::factory()->create(['max_devices' => 1]);
    $user = actingAsUser(['plan_id' => $plan->id]);
    Device::factory()->for($user)->create();

    $this->postJson('/api/devices', [
        'name' => 'Dispositivo excedente',
        'type' => 'tv',
    ])->assertStatus(429)
        ->assertJsonPath('code', 'plan_limit_reached')
        ->assertJsonPath('resource', 'devices')
        ->assertJsonPath('limit', 1);
});

test('limites negativos permitem recursos ilimitados', function () {
    Event::fake([AlertAvailable::class]);
    $plan = Plan::factory()->create(['max_devices' => -1, 'max_alerts_per_month' => -1]);
    $user = actingAsUser(['plan_id' => $plan->id]);
    Device::factory()->count(3)->for($user)->create();

    $this->postJson('/api/devices', ['name' => 'Sem limite', 'type' => 'tv'])->assertCreated();
});
