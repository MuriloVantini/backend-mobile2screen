<?php

namespace App\Console\Commands;

use App\Models\Device;
use App\Services\WebhookDispatcher;
use Illuminate\Console\Command;

class MarkStaleDevicesOffline extends Command
{
    protected $signature = 'devices:mark-offline {--minutes=2 : Minutos sem heartbeat antes de considerar offline}';

    protected $description = 'Marca dispositivos sem heartbeat recente como offline e dispara webhooks';

    public function handle(WebhookDispatcher $webhooks): int
    {
        $minutes = max(1, (int) $this->option('minutes'));
        $count = 0;

        Device::query()
            ->where('is_online', true)
            ->whereNotNull('last_seen')
            ->where('last_seen', '<', now()->subMinutes($minutes))
            ->chunkById(100, function ($devices) use ($webhooks, &$count) {
                foreach ($devices as $device) {
                    $device->update(['is_online' => false]);
                    $count++;

                    $webhooks->dispatch($device->user_id, 'device.offline', [
                        'device_id' => $device->id,
                        'name' => $device->name,
                        'last_seen' => $device->last_seen?->toIso8601String(),
                        'offline_at' => now()->toIso8601String(),
                    ]);
                }
            });

        $this->info("{$count} dispositivo(s) marcado(s) como offline.");

        return self::SUCCESS;
    }
}
