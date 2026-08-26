<?php

namespace App\Console\Commands;

use App\Models\Device;
use App\Services\OperationalNotificationService;
use Illuminate\Console\Command;

class MarkStaleDevicesOffline extends Command
{
    protected $signature = 'devices:mark-offline {--minutes=2 : Minutos sem heartbeat antes de considerar offline}';

    protected $description = 'Marca dispositivos sem heartbeat recente como offline';

    public function handle(OperationalNotificationService $notifications): int
    {
        $minutes = max(1, (int) $this->option('minutes'));
        $count = 0;

        Device::query()
            ->where('is_online', true)
            ->whereNotNull('last_seen')
            ->where('last_seen', '<', now()->subMinutes($minutes))
            ->chunkById(100, function ($devices) use (&$count, $notifications) {
                foreach ($devices as $device) {
                    $device->update(['is_online' => false]);
                    $notifications->deviceOffline($device);
                    $count++;
                }
            });

        $this->info("{$count} dispositivo(s) marcado(s) como offline.");

        return self::SUCCESS;
    }
}
