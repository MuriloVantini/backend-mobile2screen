<?php

use App\Models\Plan;
use App\Models\User;
use App\Notifications\Auth\PasswordResetPinNotification;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Notification;
use Illuminate\Support\Facades\DB;

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
        ->assertOk();
    $this->getJson('/api/plans/' . $plan->id)
        ->assertOk()
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

test('forgot password envia notificacao para email existente', function () {
    Notification::fake();

    $user = createUser([
        'email' => 'reset@example.com',
    ]);

    $this->postJson('/api/forgot-password', [
        'email' => $user->email,
    ])
        ->assertOk()
        ->assertJsonStructure(['status']);

    Notification::assertSentTo($user, PasswordResetPinNotification::class);
    $this->assertDatabaseHas('password_reset_tokens', [
        'email' => $user->email,
    ]);
});

test('forgot password valida payload', function () {
    $this->postJson('/api/forgot-password', [
        'email' => 'email-invalido',
    ])
        ->assertUnprocessable()
        ->assertJsonValidationErrors(['email']);
});

test('reset password altera a senha com pin valido', function () {
    $user = createUser([
        'email' => 'token-reset@example.com',
    ]);

    $pin = '123456';
    DB::table('password_reset_tokens')->updateOrInsert(
        ['email' => $user->email],
        [
            'token' => Hash::make($pin),
            'created_at' => now(),
        ]
    );

    $newPassword = 'NovaSenha123!';

    $this->postJson('/api/validate-reset-pin', [
        'email' => $user->email,
        'pin_code' => $pin,
    ])->assertOk();

    $this->postJson('/api/reset-password', [
        'pin_code' => $pin,
        'email' => $user->email,
        'password' => $newPassword,
        'password_confirmation' => $newPassword,
    ])
        ->assertOk()
        ->assertJsonStructure(['status']);

    expect(Hash::check($newPassword, $user->fresh()->password))->toBeTrue();
});

test('reset password exige validacao previa do pin', function () {
    $user = createUser([
        'email' => 'must-validate-pin@example.com',
    ]);

    $pin = '333333';
    DB::table('password_reset_tokens')->updateOrInsert(
        ['email' => $user->email],
        [
            'token' => Hash::make($pin),
            'created_at' => now(),
        ]
    );

    $this->postJson('/api/reset-password', [
        'pin_code' => $pin,
        'email' => $user->email,
        'password' => 'NovaSenha123!',
        'password_confirmation' => 'NovaSenha123!',
    ])
        ->assertUnprocessable()
        ->assertJsonValidationErrors(['pin_code']);
});

test('validate reset pin confirma pin valido', function () {
    $user = createUser([
        'email' => 'valid-pin@example.com',
    ]);

    $pin = '654321';
    DB::table('password_reset_tokens')->updateOrInsert(
        ['email' => $user->email],
        [
            'token' => Hash::make($pin),
            'created_at' => now(),
        ]
    );

    $this->postJson('/api/validate-reset-pin', [
        'email' => $user->email,
        'pin_code' => $pin,
    ])
        ->assertOk()
        ->assertJsonPath('status', 'PIN valido.');
});

test('validate reset pin retorna erro para pin invalido', function () {
    $user = createUser([
        'email' => 'invalid-pin@example.com',
    ]);

    DB::table('password_reset_tokens')->updateOrInsert(
        ['email' => $user->email],
        [
            'token' => Hash::make('111111'),
            'created_at' => now(),
        ]
    );

    $this->postJson('/api/validate-reset-pin', [
        'email' => $user->email,
        'pin_code' => '222222',
    ])
        ->assertUnprocessable()
        ->assertJsonValidationErrors(['pin_code']);
});
