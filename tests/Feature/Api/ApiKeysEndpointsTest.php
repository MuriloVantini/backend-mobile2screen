<?php

use App\Events\AlertAvailable;
use App\Models\ApiKey;
use App\Models\Device;
use App\Models\Tag;
use Illuminate\Support\Facades\Event;

test('endpoints de api keys: index store update e destroy', function () {
    $user = actingAsUser();
    $apiKey = ApiKey::factory()->for($user)->create();

    $this->getJson('/api/api-keys')
        ->assertOk();
    $created = $this->postJson('/api/api-keys', [
        'name' => 'Integracao ERP',
        'expires_at' => now()->addDays(10)->toISOString(),
    ])
        ->assertCreated()
        ->assertJsonPath('data.name', 'Integracao ERP');

    $this->putJson('/api/api-keys/'.$apiKey->id, [
        'name' => 'Integracao Atualizada',
        'is_active' => false,
    ])
        ->assertOk()
        ->assertJsonPath('data.name', 'Integracao Atualizada')
        ->assertJsonPath('data.is_active', false);

    $this->deleteJson('/api/api-keys/'.$created->json('data.id'))
        ->assertOk();
});

test('api key ativa autentica o envio de alerta e registra o ultimo uso', function () {
    Event::fake([AlertAvailable::class]);
    $user = createUser();
    $plainTextKey = 'sk_'.str_repeat('a', 48);
    $apiKey = ApiKey::factory()->for($user)->create([
        'key_hash' => hash('sha256', $plainTextKey),
        'is_active' => true,
        'expires_at' => now()->addDay(),
        'last_used' => null,
    ]);
    $tag = Tag::factory()->for($user)->create();
    $device = Device::factory()->for($user)->create(['is_online' => true]);
    $device->tags()->attach($tag->id);

    $this->withToken($plainTextKey)
        ->postJson('/api/alerts', [
            'title' => 'Alerta via integração',
            'message' => 'Mensagem enviada com API Key',
            'type' => 'warning',
            'duration_seconds' => 60,
            'tags' => [$tag->id],
        ])
        ->assertCreated()
        ->assertJsonPath('data.alert.user_id', $user->id);

    expect($apiKey->fresh()->last_used)->not->toBeNull();
});

test('api key nao concede acesso aos demais endpoints autenticados', function () {
    $user = createUser();
    $plainTextKey = 'sk_'.str_repeat('b', 48);
    ApiKey::factory()->for($user)->create([
        'key_hash' => hash('sha256', $plainTextKey),
        'is_active' => true,
    ]);

    $this->withToken($plainTextKey)
        ->getJson('/api/devices')
        ->assertUnauthorized();
});

test('envio rejeita api keys inativas expiradas e de usuario suspenso', function (array $attributes, ?array $userAttributes = null) {
    $plainTextKey = 'sk_'.str_repeat('c', 48);
    $user = createUser($userAttributes ?? []);
    ApiKey::factory()->for($user)->create(array_merge([
        'key_hash' => hash('sha256', $plainTextKey),
        'is_active' => true,
        'expires_at' => null,
    ], $attributes));

    $this->withToken($plainTextKey)
        ->postJson('/api/alerts', [])
        ->assertUnauthorized()
        ->assertJsonPath('message', 'Não autenticado.');
})->with([
    'inativa' => [['is_active' => false]],
    'expirada' => [['expires_at' => now()->subMinute()]],
    'usuario suspenso' => [[], ['status' => 'suspended']],
]);

test('envio rejeita api key desconhecida', function () {
    $this->withToken('sk_'.str_repeat('z', 48))
        ->postJson('/api/alerts', [])
        ->assertUnauthorized()
        ->assertJsonPath('message', 'Não autenticado.');
});
