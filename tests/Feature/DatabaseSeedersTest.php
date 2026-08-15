<?php

use App\Models\Alert;
use App\Models\AlertDelivery;
use App\Models\Device;
use App\Models\User;
use Database\Seeders\DemoUsersSeeder;

test('seeder demonstrativo cria usuarios e dados relacionados sem duplicar', function () {
    $this->seed(DemoUsersSeeder::class);
    $this->seed(DemoUsersSeeder::class);

    $demoUsers = User::query()->where('email', 'like', '%@demo.mobile2screen.local');
    $demoUserIds = (clone $demoUsers)->pluck('id');
    $demoAlertIds = Alert::query()->whereIn('user_id', $demoUserIds)->pluck('id');

    expect((clone $demoUsers)->count())->toBe(10)
        ->and(Device::query()->whereIn('user_id', $demoUserIds)->count())->toBe(35)
        ->and(Alert::query()->whereIn('user_id', $demoUserIds)->count())->toBe(124)
        ->and(AlertDelivery::query()->whereIn('alert_id', $demoAlertIds)->count())->toBe(497);

    $this->assertDatabaseHas('users', [
        'email' => 'ana.oliveira@demo.mobile2screen.local',
        'status' => 'active',
    ]);
    $this->assertDatabaseHas('users', [
        'email' => 'diego.rocha@demo.mobile2screen.local',
        'status' => 'suspended',
    ]);
    $this->assertDatabaseHas('users', [
        'email' => 'felipe.costa@demo.mobile2screen.local',
        'status' => 'pending',
    ]);
});
