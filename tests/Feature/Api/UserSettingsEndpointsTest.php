<?php

test('show de configuracoes cria padroes quando ausentes', function () {
    $user = actingAsUser();

    $this->getJson('/api/settings')
        ->assertOk()
        ->assertJsonPath('data.user_id', $user->id);
});

test('update de configuracoes persiste os dados', function () {
    actingAsUser();

    $this->putJson('/api/settings', [
        'notification_email' => 'alerts@example.com',
        'theme' => 'dark',
        'notify_weekly_report' => true,
    ])
        ->assertOk()
        ->assertJsonPath('data.notification_email', 'alerts@example.com')
        ->assertJsonPath('data.theme', 'dark')
        ->assertJsonPath('data.notify_weekly_report', true);
});
