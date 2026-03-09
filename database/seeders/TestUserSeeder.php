<?php

namespace Database\Seeders;

use App\Models\ActivityLog;
use App\Models\Alert;
use App\Models\AlertDelivery;
use App\Models\ApiKey;
use App\Models\Device;
use App\Models\Plan;
use App\Models\StatisticDaily;
use App\Models\Tag;
use App\Models\User;
use App\Models\UserSession;
use App\Models\UserSetting;
use App\Models\Webhook;
use App\Models\WebhookLog;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class TestUserSeeder extends Seeder
{
    public function run(): void
    {
        $plan = Plan::updateOrCreate(
            ['name' => 'pro'],
            Plan::factory()->pro()->make()->getAttributes()
        );

        // 1. Usuario
        $userPayload = User::factory()->make([
            'email' => 'teste@example.com',
            'name' => 'Usuario Teste',
            'password' => 'password',
            'company' => 'Empresa Teste',
            'phone' => '+55 11 99999-0000',
            'plan_id' => $plan->id,
            'status' => 'active',
            'role' => 'user',
            'last_active' => now(),
        ])->only([
            'name',
            'email',
            'password',
            'company',
            'phone',
            'plan_id',
            'status',
            'role',
            'last_active',
        ]);

        $user = User::updateOrCreate(
            ['email' => 'teste@example.com'],
            $userPayload
        );

        // 2. Configuracoes do usuario (1:1)
        $userSettingPayload = UserSetting::factory()->make([
            'user_id' => $user->id,
            'notification_email' => $user->email,
            'notification_phone' => $user->phone,
            'theme' => 'dark',
            'timezone' => 'America/Sao_Paulo',
            'language' => 'pt-BR',
        ])->getAttributes();

        UserSetting::updateOrCreate(
            ['user_id' => $user->id],
            $userSettingPayload
        );

        // 3. Dispositivo
        $devicePayload = Device::factory()->make([
            'user_id' => $user->id,
            'name' => 'TV Recepcao',
            'type' => 'tv',
            'location' => 'Recepcao - Andar 1',
            'ip_address' => '192.168.1.100',
            'mac_address' => 'AA:BB:CC:DD:EE:FF',
            'is_online' => true,
            'last_seen' => now(),
            'metadata' => ['resolution' => '1920x1080', 'firmware' => '1.0.0'],
        ])->getAttributes();

        $device = Device::firstOrCreate(
            ['user_id' => $user->id, 'name' => 'TV Recepcao'],
            $devicePayload
        );

        // 4. Tag
        $tagPayload = Tag::factory()->make([
            'user_id' => $user->id,
            'name' => 'Urgente',
            'color' => 'red',
        ])->getAttributes();

        $tag = Tag::firstOrCreate(
            ['user_id' => $user->id, 'name' => 'Urgente'],
            $tagPayload
        );

        // 5. Pivot: device_tags
        DB::table('device_tags')->insertOrIgnore([
            'device_id' => $device->id,
            'tag_id' => $tag->id,
            'created_at' => now(),
        ]);

        // 6. Alerta
        $alertPayload = Alert::factory()->make([
            'user_id' => $user->id,
            'title' => 'Alerta de Teste',
            'message' => 'Este e um alerta gerado pelo seeder de testes.',
            'type' => 'info',
            'duration_seconds' => 30,
            'priority' => 1,
            'sent_at' => now(),
            'expires_at' => now()->addDay(),
        ])->getAttributes();

        $alert = Alert::firstOrCreate(
            ['user_id' => $user->id, 'title' => 'Alerta de Teste'],
            $alertPayload
        );

        // 7. Pivot: alert_tags
        DB::table('alert_tags')->insertOrIgnore([
            'alert_id' => $alert->id,
            'tag_id' => $tag->id,
            'created_at' => now(),
        ]);

        // 8. Entrega do alerta (AlertDelivery)
        $deliveryPayload = AlertDelivery::factory()->make([
            'alert_id' => $alert->id,
            'device_id' => $device->id,
            'status' => 'delivered',
            'delivered_at' => now(),
            'acknowledged_at' => now()->addMinutes(2),
            'dismissed_at' => null,
            'error_message' => null,
            'retry_count' => 0,
        ])->getAttributes();

        AlertDelivery::firstOrCreate(
            ['alert_id' => $alert->id, 'device_id' => $device->id],
            $deliveryPayload
        );

        // 9. API Key
        $apiKeyPayload = ApiKey::factory()->make([
            'user_id' => $user->id,
            'name' => 'Chave de Teste',
            'is_active' => true,
            'last_used' => now(),
            'expires_at' => now()->addYear(),
        ])->getAttributes();

        ApiKey::firstOrCreate(
            ['user_id' => $user->id, 'name' => 'Chave de Teste'],
            $apiKeyPayload
        );

        // 10. Sessao do usuario
        $userSessionPayload = UserSession::factory()->make([
            'user_id' => $user->id,
            'token' => hash('sha256', 'test-session-token'),
            'refresh_token' => hash('sha256', 'test-refresh-token'),
            'ip_address' => '192.168.1.1',
            'user_agent' => 'Mozilla/5.0 (Test Seeder)',
            'expires_at' => now()->addDays(7),
        ])->getAttributes();

        UserSession::firstOrCreate(
            ['user_id' => $user->id, 'token' => hash('sha256', 'test-session-token')],
            $userSessionPayload
        );

        // 11. Webhook
        $webhookPayload = Webhook::factory()->make([
            'user_id' => $user->id,
            'name' => 'Webhook de Teste',
            'url' => 'https://example.com/webhook',
            'events' => ['alert.sent', 'device.offline'],
            'is_active' => true,
            'last_triggered' => now(),
        ])->getAttributes();

        $webhook = Webhook::firstOrCreate(
            ['user_id' => $user->id, 'name' => 'Webhook de Teste'],
            $webhookPayload
        );

        // 12. Log do webhook
        $webhookLogPayload = WebhookLog::factory()->make([
            'webhook_id' => $webhook->id,
            'event_type' => 'alert.sent',
            'payload' => ['alert_id' => $alert->id, 'title' => $alert->title],
            'response_status' => 200,
            'response_body' => '{"ok":true}',
            'error_message' => null,
        ])->getAttributes();

        WebhookLog::firstOrCreate(
            ['webhook_id' => $webhook->id, 'event_type' => 'alert.sent'],
            $webhookLogPayload
        );

        // 13. Log de atividade
        $activityLogPayload = ActivityLog::factory()->make([
            'user_id' => $user->id,
            'action' => 'login',
            'resource_type' => 'user',
            'resource_id' => $user->id,
            'ip_address' => '192.168.1.1',
            'user_agent' => 'Mozilla/5.0 (Test Seeder)',
            'metadata' => ['source' => 'seeder'],
        ])->getAttributes();

        ActivityLog::firstOrCreate(
            ['user_id' => $user->id, 'action' => 'login'],
            $activityLogPayload
        );

        // 14. Estatistica diaria
        $statisticDailyPayload = StatisticDaily::factory()->make([
            'user_id' => $user->id,
            'date' => now()->toDateString(),
            'alerts_sent' => 10,
            'alerts_delivered' => 9,
            'alerts_failed' => 1,
            'devices_online_avg' => 1.00,
            'delivery_rate' => 90.00,
        ])->getAttributes();

        StatisticDaily::firstOrCreate(
            ['user_id' => $user->id, 'date' => now()->toDateString()],
            $statisticDailyPayload
        );

        $this->command->info('Usuario teste@example.com criado com factories para todas as entidades.');
    }
}
