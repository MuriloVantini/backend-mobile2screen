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
use App\Models\UserSetting;
use App\Models\Webhook;
use App\Models\WebhookLog;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class DemoUsersSeeder extends Seeder
{
    /**
     * Popula contas demonstrativas e seus dados de negócio.
     */
    public function run(): void
    {
        $profiles = [
            ['name' => 'Ana Oliveira', 'email' => 'ana.oliveira@demo.mobile2screen.local', 'company' => 'Clínica Horizonte', 'phone' => '+55 11 90000-0101', 'plan' => 'pro', 'status' => 'active', 'devices' => 4, 'alerts' => 14],
            ['name' => 'Bruno Santos', 'email' => 'bruno.santos@demo.mobile2screen.local', 'company' => 'Mercado Boa Praça', 'phone' => '+55 11 90000-0102', 'plan' => 'free', 'status' => 'active', 'devices' => 2, 'alerts' => 8],
            ['name' => 'Carla Mendes', 'email' => 'carla.mendes@demo.mobile2screen.local', 'company' => 'Colégio Integração', 'phone' => '+55 11 90000-0103', 'plan' => 'enterprise', 'status' => 'active', 'devices' => 5, 'alerts' => 18],
            ['name' => 'Diego Rocha', 'email' => 'diego.rocha@demo.mobile2screen.local', 'company' => 'Hotel Central', 'phone' => '+55 11 90000-0104', 'plan' => 'pro', 'status' => 'suspended', 'devices' => 3, 'alerts' => 10],
            ['name' => 'Elisa Martins', 'email' => 'elisa.martins@demo.mobile2screen.local', 'company' => 'Indústria Polaris', 'phone' => '+55 11 90000-0105', 'plan' => 'enterprise', 'status' => 'active', 'devices' => 6, 'alerts' => 20],
            ['name' => 'Felipe Costa', 'email' => 'felipe.costa@demo.mobile2screen.local', 'company' => 'Condomínio das Flores', 'phone' => '+55 11 90000-0106', 'plan' => 'free', 'status' => 'pending', 'devices' => 1, 'alerts' => 6],
            ['name' => 'Gabriela Lima', 'email' => 'gabriela.lima@demo.mobile2screen.local', 'company' => 'Eventos Aurora', 'phone' => '+55 11 90000-0107', 'plan' => 'pro', 'status' => 'active', 'devices' => 4, 'alerts' => 12],
            ['name' => 'Henrique Alves', 'email' => 'henrique.alves@demo.mobile2screen.local', 'company' => 'Lojas Horizonte', 'phone' => '+55 11 90000-0108', 'plan' => 'free', 'status' => 'active', 'devices' => 2, 'alerts' => 9],
            ['name' => 'Isabela Nunes', 'email' => 'isabela.nunes@demo.mobile2screen.local', 'company' => 'Hospital Vida', 'phone' => '+55 11 90000-0109', 'plan' => 'enterprise', 'status' => 'active', 'devices' => 5, 'alerts' => 16],
            ['name' => 'João Ferreira', 'email' => 'joao.ferreira@demo.mobile2screen.local', 'company' => 'Universidade Metropolitana', 'phone' => '+55 11 90000-0110', 'plan' => 'pro', 'status' => 'active', 'devices' => 3, 'alerts' => 11],
        ];

        $planIds = Plan::query()
            ->whereIn('name', ['free', 'pro', 'enterprise'])
            ->pluck('id', 'name');

        if ($planIds->count() !== 3) {
            throw new \RuntimeException('Os planos free, pro e enterprise precisam existir antes do DemoUsersSeeder.');
        }

        DB::transaction(function () use ($profiles, $planIds): void {
            User::withTrashed()
                ->whereIn('email', array_column($profiles, 'email'))
                ->get()
                ->each(fn (User $user) => $user->forceDelete());

            foreach ($profiles as $profileIndex => $profile) {
                $user = User::query()->create([
                    'name' => $profile['name'],
                    'email' => $profile['email'],
                    'password' => 'password',
                    'company' => $profile['company'],
                    'phone' => $profile['phone'],
                    'plan_id' => $planIds[$profile['plan']],
                    'status' => $profile['status'],
                    'role' => 'user',
                    'last_active' => $profile['status'] === 'active'
                        ? now()->subMinutes(($profileIndex + 1) * 7)
                        : now()->subDays($profileIndex + 2),
                ]);
                $user->forceFill(['joined_at' => now()->subDays(35 + ($profileIndex * 18))])->save();

                UserSetting::factory()->create([
                    'user_id' => $user->id,
                    'notification_email' => $user->email,
                    'notification_phone' => $user->phone,
                    'theme' => $profileIndex % 3 === 0 ? 'dark' : 'light',
                ]);

                $tags = collect([
                    ['name' => 'Recepção', 'color' => 'blue'],
                    ['name' => 'Operação', 'color' => 'green'],
                    ['name' => 'Emergência', 'color' => 'red'],
                ])->map(fn (array $tag) => Tag::factory()->create([
                    'user_id' => $user->id,
                    ...$tag,
                ]));

                $devices = collect(range(1, $profile['devices']))->map(function (int $deviceIndex) use ($user, $tags, $profileIndex) {
                    $isOnline = ($deviceIndex + $profileIndex) % 4 !== 0;
                    $device = Device::factory()->create([
                        'user_id' => $user->id,
                        'name' => $deviceIndex === 1 ? 'TV Principal' : "Display {$deviceIndex}",
                        'type' => $deviceIndex % 3 === 0 ? 'rpi' : 'tv',
                        'location' => ['Recepção', 'Sala de espera', 'Corredor', 'Auditório', 'Refeitório', 'Portaria'][($deviceIndex - 1) % 6],
                        'ip_address' => "10.20." . ($profileIndex + 1) . "." . ($deviceIndex + 10),
                        'mac_address' => sprintf('02:00:%02X:%02X:%02X:%02X', $profileIndex + 1, $deviceIndex, $profileIndex + 10, $deviceIndex + 20),
                        'is_online' => $isOnline,
                        'last_seen' => $isOnline ? now()->subMinutes($deviceIndex * 2) : now()->subHours($deviceIndex + 3),
                        'connection_token' => hash('sha256', "demo-device-{$profileIndex}-{$deviceIndex}"),
                        'metadata' => [
                            'resolution' => $deviceIndex % 2 === 0 ? '3840x2160' : '1920x1080',
                            'firmware' => '1.' . ($profileIndex % 3) . '.' . $deviceIndex,
                            'seed' => 'demo-users',
                        ],
                    ]);
                    $device->tags()->sync([
                        $tags[($deviceIndex - 1) % $tags->count()]->id,
                        $tags[$deviceIndex % $tags->count()]->id,
                    ]);

                    return $device;
                });

                collect(range(1, $profile['alerts']))->each(function (int $alertIndex) use ($user, $tags, $devices, $profileIndex): void {
                    $type = ['info', 'warning', 'critical', 'success'][($alertIndex + $profileIndex) % 4];
                    $sentAt = now()
                        ->subDays(($alertIndex * 2 + $profileIndex) % 30)
                        ->subHours(($alertIndex % 8) + 1);
                    $alert = Alert::factory()->create([
                        'user_id' => $user->id,
                        'title' => $this->alertTitle($type, $alertIndex),
                        'message' => $this->alertMessage($type),
                        'type' => $type,
                        'duration_seconds' => [15, 30, 45, 60][$alertIndex % 4],
                        'priority' => $type === 'critical' ? 3 : ($type === 'warning' ? 2 : 1),
                        'sent_at' => $sentAt,
                        'expires_at' => $sentAt->copy()->addDays(2),
                        'created_at' => $sentAt,
                    ]);
                    $alert->tags()->attach($tags[($alertIndex + $profileIndex) % $tags->count()]->id, [
                        'created_at' => $sentAt,
                    ]);

                    $devices->each(function (Device $device, int $deviceIndex) use ($alert, $alertIndex, $profileIndex, $sentAt): void {
                        $statusCode = ($alertIndex + $deviceIndex + $profileIndex) % 10;
                        $status = match (true) {
                            $statusCode <= 5 => 'delivered',
                            $statusCode === 6 => 'dismissed',
                            $statusCode <= 8 => 'failed',
                            default => 'pending',
                        };
                        $wasDelivered = in_array($status, ['delivered', 'dismissed'], true);

                        AlertDelivery::factory()->create([
                            'alert_id' => $alert->id,
                            'device_id' => $device->id,
                            'status' => $status,
                            'delivered_at' => $wasDelivered ? $sentAt->copy()->addMinutes(2) : null,
                            'acknowledged_at' => $status === 'dismissed' ? $sentAt->copy()->addMinutes(4) : null,
                            'dismissed_at' => $status === 'dismissed' ? $sentAt->copy()->addMinutes(6) : null,
                            'error_message' => $status === 'failed' ? 'Dispositivo indisponível durante a entrega demonstrativa' : null,
                            'retry_count' => $status === 'failed' ? 1 : 0,
                            'created_at' => $sentAt,
                        ]);
                    });
                });

                collect(range(0, 13))->each(function (int $dayOffset) use ($user, $profile, $profileIndex): void {
                    $alertsSent = max(0, (($profile['alerts'] + $dayOffset + $profileIndex) % 8) - 1);
                    $alertsFailed = $alertsSent > 0 && ($dayOffset + $profileIndex) % 4 === 0 ? 1 : 0;
                    $alertsDelivered = $alertsSent - $alertsFailed;

                    StatisticDaily::factory()->create([
                        'user_id' => $user->id,
                        'date' => now()->subDays($dayOffset)->toDateString(),
                        'alerts_sent' => $alertsSent,
                        'alerts_delivered' => $alertsDelivered,
                        'alerts_failed' => $alertsFailed,
                        'devices_online_avg' => max(0, $profile['devices'] - (($dayOffset + $profileIndex) % 2)),
                        'delivery_rate' => $alertsSent > 0 ? round(($alertsDelivered / $alertsSent) * 100, 2) : 0,
                    ]);
                });

                collect(['login', 'device_added', 'alert_sent', 'login', 'alert_sent'])
                    ->each(fn (string $action, int $index) => ActivityLog::factory()->create([
                        'user_id' => $user->id,
                        'action' => $action,
                        'resource_type' => $action === 'device_added' ? 'device' : ($action === 'alert_sent' ? 'alert' : 'user'),
                        'resource_id' => $user->id,
                        'ip_address' => "10.30." . ($profileIndex + 1) . "." . ($index + 20),
                        'metadata' => ['source' => 'demo-users-seeder'],
                        'created_at' => now()->subDays($index)->subMinutes($profileIndex * 3),
                    ]));

                ApiKey::factory()->create([
                    'user_id' => $user->id,
                    'key_hash' => hash('sha256', "demo-api-key-{$profileIndex}"),
                    'name' => 'Integração demonstrativa',
                    'is_active' => $profile['status'] === 'active',
                ]);

                if ($profile['plan'] !== 'free') {
                    $webhook = Webhook::factory()->create([
                        'user_id' => $user->id,
                        'name' => 'Endpoint demonstrativo',
                        'url' => "https://example.invalid/mobile2screen/{$profileIndex}",
                        'secret' => hash('sha256', "demo-webhook-secret-{$profileIndex}"),
                        'events' => ['alert.sent', 'device.offline'],
                        'is_active' => false,
                        'last_triggered' => now()->subDays($profileIndex + 1),
                    ]);
                    WebhookLog::factory()->create([
                        'webhook_id' => $webhook->id,
                        'event_type' => 'alert.sent',
                        'payload' => ['source' => 'demo-users-seeder'],
                        'response_status' => 200,
                        'response_body' => '{"demo":true}',
                        'created_at' => now()->subDays($profileIndex + 1),
                    ]);
                }
            }
        });

        $this->command?->info('10 usuários demonstrativos criados com dispositivos, alertas, entregas e dados relacionados.');
    }

    private function alertTitle(string $type, int $index): string
    {
        return match ($type) {
            'critical' => "Atenção imediata #{$index}",
            'warning' => "Aviso operacional #{$index}",
            'success' => "Operação concluída #{$index}",
            default => "Comunicado geral #{$index}",
        };
    }

    private function alertMessage(string $type): string
    {
        return match ($type) {
            'critical' => 'Dirija-se ao ponto de apoio e siga as orientações da equipe responsável.',
            'warning' => 'Uma atividade programada poderá afetar temporariamente este ambiente.',
            'success' => 'O procedimento foi finalizado e o funcionamento está normalizado.',
            default => 'Confira as informações atualizadas exibidas neste painel.',
        };
    }
}
