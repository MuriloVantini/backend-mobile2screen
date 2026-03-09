<?php

namespace Database\Seeders;

use App\Models\Alert;
use App\Models\AlertDelivery;
use App\Models\Device;
use App\Models\Tag;
use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

class GenerateDeliveries extends Seeder
{
    public function run(): void
    {
        $user = User::where('email', 'teste@example.com')->first();

        if (! $user) {
            $this->command->error('Usuario teste@example.com nao encontrado. Rode o TestUserSeeder primeiro.');

            return;
        }

        $tagDefinitions = [
            ['name' => 'Urgente', 'color' => 'red'],
            ['name' => 'Comercial', 'color' => 'blue'],
            ['name' => 'Institucional', 'color' => 'green'],
            ['name' => 'Financeiro', 'color' => 'yellow'],
            ['name' => 'TI', 'color' => 'gray'],
        ];

        $devicesToCreate = 15;
        $alertsPerTag = 6;

        $tags = collect($tagDefinitions)->map(function (array $tagData) use ($user) {
            return Tag::firstOrCreate(
                [
                    'user_id' => $user->id,
                    'name' => $tagData['name'],
                ],
                [
                    'color' => $tagData['color'],
                ]
            );
        })->values();

        $devices = collect();

        for ($deviceIndex = 1; $deviceIndex <= $devicesToCreate; $deviceIndex++) {
            $device = Device::firstOrCreate(
                [
                    'user_id' => $user->id,
                    'name' => sprintf('Display %02d', $deviceIndex),
                ],
                [
                    'type' => 'tv',
                    'location' => sprintf('Setor %02d', (($deviceIndex - 1) % 5) + 1),
                    'ip_address' => sprintf('10.0.0.%d', $deviceIndex),
                    'mac_address' => sprintf('AA:BB:CC:%02X:%02X:%02X', $deviceIndex, $deviceIndex, $deviceIndex),
                    'is_online' => true,
                    'last_seen' => now(),
                    'connection_token' => Str::random(64),
                    'metadata' => [
                        'resolution' => '1920x1080',
                        'firmware' => '2.0.0',
                        'source' => 'GenerateDeliveries',
                    ],
                ]
            );

            $devices->push($device);

            // Cada device fica associado a 2 tags para criar distribuicao em varias tags.
            $primaryTagIndex = ($deviceIndex - 1) % $tags->count();
            $secondaryTagIndex = $deviceIndex % $tags->count();

            DB::table('device_tags')->insertOrIgnore([
                [
                    'device_id' => $device->id,
                    'tag_id' => $tags[$primaryTagIndex]->id,
                    'created_at' => now(),
                ],
                [
                    'device_id' => $device->id,
                    'tag_id' => $tags[$secondaryTagIndex]->id,
                    'created_at' => now(),
                ],
            ]);
        }

        $totalAlerts = 0;
        $totalDeliveries = 0;

        foreach ($tags as $tag) {
            for ($alertIndex = 1; $alertIndex <= $alertsPerTag; $alertIndex++) {
                $alert = Alert::firstOrCreate(
                    [
                        'user_id' => $user->id,
                        'title' => sprintf('[%s] Alerta %02d', $tag->name, $alertIndex),
                    ],
                    [
                        'message' => sprintf('Mensagem automatica do alerta %02d para a tag %s.', $alertIndex, $tag->name),
                        'type' => 'info',
                        'duration_seconds' => 30,
                        'priority' => 1,
                        'sent_at' => now()->subMinutes(5),
                        'expires_at' => now()->addDays(2),
                    ]
                );

                DB::table('alert_tags')->insertOrIgnore([
                    'alert_id' => $alert->id,
                    'tag_id' => $tag->id,
                    'created_at' => now(),
                ]);

                $tagDeviceIds = $tag->devices()->pluck('devices.id');

                foreach ($tagDeviceIds as $deviceId) {
                    $status = ((int) $deviceId + $alertIndex) % 5 === 0 ? 'failed' : 'delivered';

                    $delivery = AlertDelivery::firstOrCreate(
                        [
                            'alert_id' => $alert->id,
                            'device_id' => $deviceId,
                        ],
                        [
                            'status' => $status,
                            'delivered_at' => $status === 'delivered' ? now()->subMinute() : null,
                            'acknowledged_at' => $status === 'delivered' ? now() : null,
                            'dismissed_at' => null,
                            'error_message' => $status === 'failed' ? 'Falha simulada de rede' : null,
                            'retry_count' => $status === 'failed' ? 1 : 0,
                        ]
                    );

                    if ($delivery->wasRecentlyCreated) {
                        $totalDeliveries++;
                    }
                }

                if ($alert->wasRecentlyCreated) {
                    $totalAlerts++;
                }
            }
        }

        $this->command->info('Seeder GenerateDeliveries concluido para teste@example.com');
        $this->command->info("Tags processadas: {$tags->count()}");
        $this->command->info("Devices processados: {$devices->count()}");
        $this->command->info("Alertas novos: {$totalAlerts}");
        $this->command->info("Deliveries novas: {$totalDeliveries}");
    }
}
