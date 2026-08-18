<?php

use App\Models\User;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;

test('index de usuarios retorna dados paginados para admin', function () {
    actingAsUser(['role' => 'admin']);

    foreach (range(1, 3) as $index) {
        createUser(['email' => "admin-list-{$index}@example.com"]);
    }

    $this->getJson('/api/users')
        ->assertOk()
        ->assertJsonStructure(['data' => ['data', 'current_page', 'total']]);
});

test('index de usuarios retorna apenas o usuario atual para nao admin', function () {
    $user = actingAsUser(['role' => 'user']);

    foreach (range(1, 2) as $index) {
        createUser(['email' => "user-list-{$index}@example.com"]);
    }

    $this->getJson('/api/users')
        ->assertOk()
        ->assertJsonCount(1, 'data')
        ->assertJsonPath('data.0.id', $user->id);
});

test('store de usuarios em rota protegida permite apenas admin', function () {
    actingAsUser(['role' => 'admin']);

    $this->postJson('/api/users', [
        'name' => 'Funcionario',
        'email' => 'funcionario@example.com',
        'password' => 'Password123!',
        'password_confirmation' => 'Password123!',
        'role' => 'admin',
    ])
        ->assertCreated()
        ->assertJsonPath('data.role', 'admin');

    actingAsUser(['role' => 'user']);

    $this->postJson('/api/users', [
        'name' => 'Sem Acesso',
        'email' => 'sem-acesso@example.com',
        'password' => 'Password123!',
        'password_confirmation' => 'Password123!',
    ])->assertForbidden();
});

test('show de usuarios respeita propriedade do recurso', function () {
    $owner = actingAsUser();
    $other = createUser();

    $this->getJson('/api/users/' . $owner->id)
        ->assertOk()
        ->assertJsonPath('data.id', $owner->id);

    $this->getJson('/api/users/' . $other->id)->assertForbidden();
});

test('update de usuarios permite autoedicao mas bloqueia troca de perfil para nao admin', function () {
    $user = actingAsUser(['role' => 'user']);

    $this->putJson('/api/users/' . $user->id, [
        'name' => 'Nome Atualizado',
        'role' => 'admin',
    ])
        ->assertOk()
        ->assertJsonPath('data.role', 'user')
        ->assertJsonPath('data.name', 'Nome Atualizado');
});

test('destroy de usuarios permite excluir a propria conta', function () {
    $user = actingAsUser();

    $this->deleteJson('/api/users/' . $user->id)
        ->assertOk();
    expect(User::withTrashed()->find($user->id))->not->toBeNull();
});

test('usuario pode enviar e remover a propria foto de perfil', function () {
    Storage::fake('public');
    $user = actingAsUser();

    $response = $this->post('/api/users/' . $user->id . '/profile-image', [
        'image' => UploadedFile::fake()->image('perfil.png', 320, 320),
    ], ['Accept' => 'application/json']);

    $response
        ->assertOk()
        ->assertJsonPath('data.id', $user->id)
        ->assertJsonPath('data.profile_image_url', fn ($url) => str_contains($url, '/storage/profile-images/'));

    $storedPath = $user->fresh()->profile_image_path;
    expect($storedPath)->not->toBeNull();
    Storage::disk('public')->assertExists($storedPath);

    $this->deleteJson('/api/users/' . $user->id . '/profile-image')
        ->assertOk()
        ->assertJsonPath('data.profile_image_url', null);

    Storage::disk('public')->assertMissing($storedPath);
});

test('usuario nao pode alterar a foto de perfil de outra conta', function () {
    Storage::fake('public');
    actingAsUser();
    $other = createUser();

    $this->post('/api/users/' . $other->id . '/profile-image', [
        'image' => UploadedFile::fake()->image('perfil.png'),
    ], ['Accept' => 'application/json'])->assertForbidden();
});
