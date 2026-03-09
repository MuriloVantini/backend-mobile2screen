<?php

use App\Models\Device;
use App\Models\Tag;

test('endpoints de tags: index store show update destroy e listagem de dispositivos', function () {
    $user = actingAsUser();

    $tag = Tag::factory()->for($user)->create([
        'name' => 'Recepcao',
    ]);

    $device = Device::factory()->for($user)->create();
    $device->tags()->attach($tag->id);

    $this->getJson('/api/tags')
        ->assertOk();
    $created = $this->postJson('/api/tags', [
        'name' => 'Urgente',
        'color' => 'red',
    ])
        ->assertCreated()
        ->assertJsonPath('data.name', 'Urgente');

    $createdId = $created->json('data.id');

    $this->getJson('/api/tags/' . $tag->id)
        ->assertOk();
    $this->putJson('/api/tags/' . $tag->id, [
        'name' => 'Recepcao Atualizada',
    ])
        ->assertOk()
        ->assertJsonPath('data.name', 'Recepcao Atualizada');

    $this->getJson('/api/tags/' . $tag->id . '/devices')
        ->assertOk()
        ->assertJsonCount(1, 'data');

    $this->deleteJson('/api/tags/' . $createdId)
        ->assertOk();
});
