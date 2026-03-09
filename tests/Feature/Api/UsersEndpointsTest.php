<?php

use App\Models\User;

test('users index returns paginated data for admin', function () {
    actingAsUser(['role' => 'admin']);

    foreach (range(1, 3) as $index) {
        createUser(['email' => "admin-list-{$index}@example.com"]);
    }

    $this->getJson('/api/users')
        ->assertOk()
        ->assertJsonPath('success', true)
        ->assertJsonStructure(['data' => ['data', 'current_page', 'total']]);
});

test('users index returns only current user for non admin', function () {
    $user = actingAsUser(['role' => 'user']);

    foreach (range(1, 2) as $index) {
        createUser(['email' => "user-list-{$index}@example.com"]);
    }

    $this->getJson('/api/users')
        ->assertOk()
        ->assertJsonPath('success', true)
        ->assertJsonCount(1, 'data')
        ->assertJsonPath('data.0.id', $user->id);
});

test('users store via protected route allows only admin', function () {
    actingAsUser(['role' => 'admin']);

    $this->postJson('/api/users', [
        'name' => 'Funcionario',
        'email' => 'funcionario@example.com',
        'password' => 'Password123!',
        'password_confirmation' => 'Password123!',
        'role' => 'admin',
    ])
        ->assertCreated()
        ->assertJsonPath('success', true)
        ->assertJsonPath('data.role', 'admin');

    actingAsUser(['role' => 'user']);

    $this->postJson('/api/users', [
        'name' => 'Sem Acesso',
        'email' => 'sem-acesso@example.com',
        'password' => 'Password123!',
        'password_confirmation' => 'Password123!',
    ])->assertForbidden();
});

test('users show respects ownership', function () {
    $owner = actingAsUser();
    $other = createUser();

    $this->getJson('/api/users/' . $owner->id)
        ->assertOk()
        ->assertJsonPath('data.id', $owner->id);

    $this->getJson('/api/users/' . $other->id)->assertForbidden();
});

test('users update allows self but blocks role change for non admin', function () {
    $user = actingAsUser(['role' => 'user']);

    $this->putJson('/api/users/' . $user->id, [
        'name' => 'Nome Atualizado',
        'role' => 'admin',
    ])
        ->assertOk()
        ->assertJsonPath('success', true)
        ->assertJsonPath('data.role', 'user')
        ->assertJsonPath('data.name', 'Nome Atualizado');
});

test('users destroy allows deleting own account', function () {
    $user = actingAsUser();

    $this->deleteJson('/api/users/' . $user->id)
        ->assertOk()
        ->assertJsonPath('success', true);

    expect(User::withTrashed()->find($user->id))->not->toBeNull();
});
