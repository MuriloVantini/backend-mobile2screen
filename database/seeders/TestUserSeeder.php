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
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;

class TestUserSeeder extends Seeder
{
    public function run(): void
    {
        $plan = Plan::where('name', 'pro')->first();

        // 1. Usuário
        $user = User::updateOrCreate(
            ['email' => 'teste@example.com'],
            [
                'name'        => 'Usuário Teste',
                'password'    => Hash::make('password'),
                'company'     => 'Empresa Teste',
                'phone'       => '+55 11 99999-0000',
                'plan_id'     => $plan?->id,
                'status'      => 'active',
                'role'        => 'user',
                'last_active' => now(),
            ]
        );

        // 2. Configurações do usuário (1:1)
        UserSetting::updateOrCreate(
            ['user_id' => $user->id],
            [
                'notify_alert_failed'     => true,
                'notify_device_offline'   => true,
                'notify_weekly_report'    => true,
                'notify_device_connected' => true,
                'notify_limit_reached'    => true,
                'notification_email'      => $user->email,
                'notification_phone'      => $user->phone,
                'timezone'                => 'America/Sao_Paulo',
                'language'                => 'pt-BR',
                'theme'                   => 'dark',
            ]
        );

        // 3. Dispositivo
        $device = Device::firstOrCreate(
            ['user_id' => $user->id, 'name' => 'TV Recepção'],
            [
                'type'             => 'tv',
                'location'         => 'Recepção - Andar 1',
                'ip_address'       => '192.168.1.100',
                'mac_address'      => 'AA:BB:CC:DD:EE:FF',
                'is_online'        => true,
                'last_seen'        => now(),
                'connection_token' => Str::random(64),
                'metadata'         => ['resolution' => '1920x1080', 'firmware' => '1.0.0'],
            ]
        );

        // 4. Tag
        $tag = Tag::firstOrCreate(
            ['user_id' => $user->id, 'name' => 'Urgente'],
            ['color' => 'red']
        );

        // 5. Pivot: device_tags
        DB::table('device_tags')->insertOrIgnore([
            'device_id'  => $device->id,
            'tag_id'     => $tag->id,
            'created_at' => now(),
        ]);

        // 6. Alerta
        $alert = Alert::firstOrCreate(
            ['user_id' => $user->id, 'title' => 'Alerta de Teste'],
            [
                'message'          => 'Este é um alerta gerado pelo seeder de testes.',
                'type'             => 'info',
                'duration_seconds' => 30,
                'priority'         => 1,
                'sent_at'          => now(),
                'expires_at'       => now()->addDay(),
            ]
        );

        // 7. Pivot: alert_tags
        DB::table('alert_tags')->insertOrIgnore([
            'alert_id'   => $alert->id,
            'tag_id'     => $tag->id,
            'created_at' => now(),
        ]);

        // 8. Entrega do alerta (AlertDelivery)
        AlertDelivery::firstOrCreate(
            ['alert_id' => $alert->id, 'device_id' => $device->id],
            [
                'status'          => 'delivered',
                'delivered_at'    => now(),
                'acknowledged_at' => now()->addMinutes(2),
                'dismissed_at'    => null,
                'error_message'   => null,
                'retry_count'     => 0,
            ]
        );

        // 9. API Key
        $rawKey = Str::random(40);
        ApiKey::firstOrCreate(
            ['user_id' => $user->id, 'name' => 'Chave de Teste'],
            [
                'key_hash'  => hash('sha256', $rawKey),
                'last_used' => now(),
                'expires_at' => now()->addYear(),
                'is_active' => true,
            ]
        );

        // 10. Sessão do usuário
        UserSession::firstOrCreate(
            ['user_id' => $user->id, 'token' => hash('sha256', 'test-session-token')],
            [
                'refresh_token' => hash('sha256', 'test-refresh-token'),
                'ip_address'    => '192.168.1.1',
                'user_agent'    => 'Mozilla/5.0 (Test Seeder)',
                'expires_at'    => now()->addDays(7),
            ]
        );

        // 11. Webhook
        $webhook = Webhook::firstOrCreate(
            ['user_id' => $user->id, 'name' => 'Webhook de Teste'],
            [
                'url'            => 'https://example.com/webhook',
                'secret'         => Str::random(32),
                'events'         => ['alert.sent', 'device.offline'],
                'is_active'      => true,
                'last_triggered' => now(),
            ]
        );

        // 12. Log do webhook
        WebhookLog::firstOrCreate(
            ['webhook_id' => $webhook->id, 'event_type' => 'alert.sent'],
            [
                'payload'         => ['alert_id' => $alert->id, 'title' => $alert->title],
                'response_status' => 200,
                'response_body'   => '{"ok":true}',
                'error_message'   => null,
            ]
        );

        // 13. Log de atividade
        ActivityLog::firstOrCreate(
            ['user_id' => $user->id, 'action' => 'login'],
            [
                'resource_type' => 'user',
                'resource_id'   => $user->id,
                'ip_address'    => '192.168.1.1',
                'user_agent'    => 'Mozilla/5.0 (Test Seeder)',
                'metadata'      => ['source' => 'seeder'],
            ]
        );

        // 14. Estatística diária
        StatisticDaily::firstOrCreate(
            ['user_id' => $user->id, 'date' => now()->toDateString()],
            [
                'alerts_sent'        => 10,
                'alerts_delivered'   => 9,
                'alerts_failed'      => 1,
                'devices_online_avg' => 1.00,
                'delivery_rate'      => 90.00,
            ]
        );

        $this->command->info("Usuário teste@example.com criado com todos os registros de entidade.");
    }
}
