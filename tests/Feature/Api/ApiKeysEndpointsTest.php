<?php

use App\Models\ApiKey;

test('endpoints de api keys: index store update e destroy', function () {
    $user = actingAsUser();
    $apiKey = ApiKey::factory()->for($user)->create();

    $this->getJson('/api/api-keys')
        ->assertOk()
        ->assertJsonPath('success', true);

    $created = $this->postJson('/api/api-keys', [
        'name' => 'Integracao ERP',
        'expires_at' => now()->addDays(10)->toISOString(),
    ])
        ->assertCreated()
        ->assertJsonPath('success', true)
        ->assertJsonPath('data.name', 'Integracao ERP');

    $this->putJson('/api/api-keys/' . $apiKey->id, [
        'name' => 'Integracao Atualizada',
        'is_active' => false,
    ])
        ->assertOk()
        ->assertJsonPath('success', true)
        ->assertJsonPath('data.name', 'Integracao Atualizada')
        ->assertJsonPath('data.is_active', false);

    $this->deleteJson('/api/api-keys/' . $created->json('data.id'))
        ->assertOk()
        ->assertJsonPath('success', true);
});
