<?php

test('settings show creates defaults when missing', function () {
    $user = actingAsUser();

    $this->getJson('/api/settings')
        ->assertOk()
        ->assertJsonPath('success', true)
        ->assertJsonPath('data.user_id', $user->id);
});

test('settings update persists data', function () {
    actingAsUser();

    $this->putJson('/api/settings', [
        'notification_email' => 'alerts@example.com',
        'theme' => 'dark',
        'notify_weekly_report' => true,
    ])
        ->assertOk()
        ->assertJsonPath('success', true)
        ->assertJsonPath('data.notification_email', 'alerts@example.com')
        ->assertJsonPath('data.theme', 'dark')
        ->assertJsonPath('data.notify_weekly_report', true);
});
