<?php

use App\Events\AlertAvailable;
use App\Models\ActivityLog;
use App\Models\Alert;
use App\Models\AlertDelivery;
use App\Models\Device;
use App\Models\Plan;
use App\Models\Tag;
use App\Models\UserSetting;
use App\Notifications\OperationalNotification;
use Illuminate\Support\Facades\Event;
use Illuminate\Support\Facades\Notification;

test('falha de alerta envia email para o endereco configurado', function () {
    Notification::fake();
    Event::fake([AlertAvailable::class]);
    $user = actingAsUser();
    UserSetting::factory()->for($user)->create([
        'notify_alert_failed' => true,
        'notify_limit_reached' => false,
        'notification_email' => 'operacoes@example.com',
    ]);
    $tag = Tag::factory()->for($user)->create();
    $device = Device::factory()->for($user)->create(['is_online' => false]);
    $device->tags()->attach($tag->id);

    $this->postJson('/api/alerts', [
        'title' => 'Teste com falha',
        'message' => 'Dispositivo offline',
        'type' => 'warning',
        'tags' => [$tag->id],
    ])->assertCreated();

    Notification::assertSentOnDemand(
        OperationalNotification::class,
        fn (OperationalNotification $notification, array $channels, object $notifiable) => $notification->kind === 'alert_failed'
            && $notifiable->routes['mail'] === 'operacoes@example.com',
    );
});

test('preferencia desabilitada impede email de falha de alerta', function () {
    Notification::fake();
    Event::fake([AlertAvailable::class]);
    $user = actingAsUser();
    UserSetting::factory()->for($user)->create([
        'notify_alert_failed' => false,
        'notify_limit_reached' => false,
    ]);
    $tag = Tag::factory()->for($user)->create();
    $device = Device::factory()->for($user)->create(['is_online' => false]);
    $device->tags()->attach($tag->id);

    $this->postJson('/api/alerts', [
        'title' => 'Teste sem email',
        'message' => 'A preferência está desabilitada',
        'type' => 'warning',
        'tags' => [$tag->id],
    ])->assertCreated();

    Notification::assertNothingSent();
});

test('transicao para offline envia uma unica notificacao', function () {
    Notification::fake();
    $user = createUser();
    UserSetting::factory()->for($user)->create([
        'notify_device_offline' => true,
        'notification_email' => 'infraestrutura@example.com',
    ]);
    $device = Device::factory()->for($user)->create([
        'is_online' => true,
        'last_seen' => now()->subMinutes(10),
    ]);

    $this->artisan('devices:mark-offline', ['--minutes' => 2])->assertSuccessful();
    $this->artisan('devices:mark-offline', ['--minutes' => 2])->assertSuccessful();

    expect($device->fresh()->is_online)->toBeFalse();
    Notification::assertSentOnDemandTimes(OperationalNotification::class, 1);
    Notification::assertSentOnDemand(
        OperationalNotification::class,
        fn (OperationalNotification $notification) => $notification->kind === 'device_offline',
    );
});

test('reconexao do kiosk envia uma unica notificacao de dispositivo conectado', function () {
    Notification::fake();
    $user = createUser();
    UserSetting::factory()->for($user)->create(['notify_device_connected' => true]);
    $device = Device::factory()->for($user)->create(['is_online' => false]);
    $headers = ['X-Device-Token' => $device->connection_token];

    $this->withHeaders($headers)
        ->postJson('/api/kiosk/devices/'.$device->id.'/connect')
        ->assertOk();
    $this->withHeaders($headers)
        ->postJson('/api/kiosk/devices/'.$device->id.'/heartbeat')
        ->assertOk();

    Notification::assertSentOnDemandTimes(OperationalNotification::class, 1);
    Notification::assertSentOnDemand(
        OperationalNotification::class,
        fn (OperationalNotification $notification) => $notification->kind === 'device_connected',
    );
});

test('comando semanal envia relatorio somente para quem habilitou a preferencia', function () {
    Notification::fake();
    $enabledUser = createUser(['email' => 'relatorio@example.com']);
    $disabledUser = createUser(['email' => 'sem-relatorio@example.com']);
    UserSetting::factory()->for($enabledUser)->create([
        'notify_weekly_report' => true,
        'notification_email' => null,
    ]);
    UserSetting::factory()->for($disabledUser)->create(['notify_weekly_report' => false]);
    $sentAt = now()->subWeek()->startOfWeek()->addDay();
    $alert = Alert::factory()->for($enabledUser)->create(['sent_at' => $sentAt]);
    $device = Device::factory()->for($enabledUser)->create(['is_online' => true]);
    AlertDelivery::factory()->for($alert)->for($device)->create([
        'status' => 'delivered',
        'delivered_at' => $sentAt,
    ]);

    $this->artisan('notifications:weekly-report')->assertSuccessful();
    $this->artisan('notifications:weekly-report')->assertSuccessful();

    Notification::assertSentOnDemandTimes(OperationalNotification::class, 1);
    Notification::assertSentOnDemand(
        OperationalNotification::class,
        fn (OperationalNotification $notification, array $channels, object $notifiable) => $notification->kind === 'weekly_report'
            && $notifiable->routes['mail'] === 'relatorio@example.com',
    );
    expect(ActivityLog::query()
        ->where('user_id', $enabledUser->id)
        ->where('action', 'notification.weekly_report')
        ->count())->toBe(1);
});

test('aviso de limite e enviado uma vez por mes ao atingir oitenta por cento', function () {
    Notification::fake();
    Event::fake([AlertAvailable::class]);
    $plan = Plan::factory()->create(['max_alerts_per_month' => 2]);
    $user = actingAsUser(['plan_id' => $plan->id]);
    UserSetting::factory()->for($user)->create([
        'notify_alert_failed' => false,
        'notify_limit_reached' => true,
    ]);
    Alert::factory()->for($user)->create(['sent_at' => now()]);
    $tag = Tag::factory()->for($user)->create();
    $device = Device::factory()->for($user)->create(['is_online' => true]);
    $device->tags()->attach($tag->id);
    $payload = [
        'title' => 'Alerta próximo ao limite',
        'message' => 'Teste de limite mensal',
        'type' => 'info',
        'tags' => [$tag->id],
    ];

    $this->postJson('/api/alerts', $payload)->assertCreated();
    $this->postJson('/api/alerts', $payload)->assertCreated();

    Notification::assertSentOnDemandTimes(OperationalNotification::class, 1);
    Notification::assertSentOnDemand(
        OperationalNotification::class,
        fn (OperationalNotification $notification) => $notification->kind === 'alert_limit_reached',
    );
    expect(ActivityLog::query()
        ->where('user_id', $user->id)
        ->where('action', 'notification.alert_limit_reached')
        ->count())->toBe(1);
});
