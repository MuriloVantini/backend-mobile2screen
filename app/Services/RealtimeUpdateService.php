<?php

namespace App\Services;

use App\Events\SystemStateChanged;
use Illuminate\Support\Facades\Log;

class RealtimeUpdateService
{
    public function publish(int $userId, string $resource): void
    {
        try {
            SystemStateChanged::dispatch($userId, $resource);
        } catch (\Throwable $exception) {
            Log::warning('Não foi possível publicar a atualização em tempo real.', [
                'user_id' => $userId,
                'resource' => $resource,
                'exception' => $exception->getMessage(),
            ]);
        }
    }
}
