<?php

use App\Models\User;
use Laravel\Sanctum\Sanctum;

if (! function_exists('createUser')) {
    function createUser(array $attributes = []): User
    {
        $defaults = [
            'name' => 'User ' . fake()->unique()->numerify('###'),
            'email' => fake()->unique()->safeEmail(),
            'password' => 'password',
            'status' => 'active',
            'role' => 'user',
        ];

        return User::query()->create(array_merge($defaults, $attributes));
    }
}

if (! function_exists('actingAsUser')) {
    function actingAsUser(array $attributes = []): User
    {
        $user = createUser($attributes);
        Sanctum::actingAs($user);

        return $user;
    }
}
