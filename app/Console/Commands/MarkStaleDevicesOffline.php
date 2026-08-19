<?php

namespace App\Console\Commands;

use App\Models\Device;
use Illuminate\Console\Command;

class MarkStaleDevicesOffline extends Command
{
    protected $signature = 'devices:mark-offline {--minutes=2 : Minutos sem heartbeat antes de considerar offline}';

    protected $description = 'Marca dispositivos sem heartbeat recente como offline';

    public function handle(): int
    {
        $minutes = max(1, (int) $this->option('minutes'));
        $count = 0;

        Device::query()
            ->where('is_online', true)
            ->whereNotNull('last_seen')
            ->where('last_seen', '<', now()->subMinutes($minutes))
            ->chunkById(100, function ($devices) use (&$count) {
                foreach ($devices as $device) {
                    $device->update(['is_online' => false]);
                    $count++;
                }
            });

        $this->info("{$count} dispositivo(s) marcado(s) como offline.");

        return self::SUCCESS;
    }
}
