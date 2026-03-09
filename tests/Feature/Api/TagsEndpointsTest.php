<?php

use App\Models\Device;
use App\Models\Tag;

test('tags endpoints: index store show update destroy and devices list', function () {
    $user = actingAsUser();

    $tag = Tag::factory()->for($user)->create([
        'name' => 'Recepcao',
    ]);

    $device = Device::factory()->for($user)->create();
    $device->tags()->attach($tag->id);

    $this->getJson('/api/tags')
        ->assertOk()
        ->assertJsonPath('success', true);

    $created = $this->postJson('/api/tags', [
        'name' => 'Urgente',
        'color' => 'red',
    ])
        ->assertCreated()
        ->assertJsonPath('success', true)
        ->assertJsonPath('data.name', 'Urgente');

    $createdId = $created->json('data.id');

    $this->getJson('/api/tags/' . $tag->id)
        ->assertOk()
        ->assertJsonPath('success', true);

    $this->putJson('/api/tags/' . $tag->id, [
        'name' => 'Recepcao Atualizada',
    ])
        ->assertOk()
        ->assertJsonPath('data.name', 'Recepcao Atualizada');

    $this->getJson('/api/tags/' . $tag->id . '/devices')
        ->assertOk()
        ->assertJsonPath('success', true)
        ->assertJsonCount(1, 'data');

    $this->deleteJson('/api/tags/' . $createdId)
        ->assertOk()
        ->assertJsonPath('success', true);
});
