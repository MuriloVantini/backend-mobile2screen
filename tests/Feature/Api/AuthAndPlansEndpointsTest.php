<?php

use App\Models\Plan;
use App\Models\User;

test('endpoints publicos: registro, login e planos estao acessiveis', function () {
    $registerResponse = $this->postJson('/api/register', [
        'name' => 'Novo Usuario',
        'email' => 'novo@example.com',
        'password' => 'Password123!',
        'password_confirmation' => 'Password123!',
        'role' => 'admin',
        'status' => 'suspended',
    ]);

    $registerResponse
        ->assertCreated()
        ->assertJsonPath('success', true)
        ->assertJsonPath('data.role', 'user')
        ->assertJsonPath('data.status', 'active');

    $loginUser = createUser([
        'email' => 'login@example.com',
        'password' => 'password',
    ]);

    $this->postJson('/api/login', [
        'email' => $loginUser->email,
        'password' => 'password',
    ])
        ->assertOk()
        ->assertJsonStructure(['message', 'user', 'token', 'token_type']);

    $plan = Plan::query()->firstOrFail();

    $this->getJson('/api/plans')
        ->assertOk()
        ->assertJsonPath('success', true);

    $this->getJson('/api/plans/' . $plan->id)
        ->assertOk()
        ->assertJsonPath('success', true)
        ->assertJsonPath('data.id', $plan->id);
});

test('endpoints protegidos exigem autenticacao', function () {
    $this->getJson('/api/user')->assertUnauthorized();
});

test('endpoint de usuario retorna usuario autenticado com plano', function () {
    $plan = Plan::query()->firstOrFail();
    $user = actingAsUser(['plan_id' => $plan->id]);

    $this->getJson('/api/user')
        ->assertOk()
        ->assertJsonPath('success', true)
        ->assertJsonPath('data.id', $user->id)
        ->assertJsonPath('data.plan.id', $plan->id);
});

test('logout revoga o token atual', function () {
    $user = createUser();
    $token = $user->createToken('auth_token')->plainTextToken;

    $this->withHeader('Authorization', 'Bearer ' . $token)
        ->postJson('/api/logout')
        ->assertOk()
        ->assertJsonStructure(['message']);

    $this->assertDatabaseCount('personal_access_tokens', 0);
});
