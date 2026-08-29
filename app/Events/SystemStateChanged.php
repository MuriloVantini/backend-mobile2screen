<?php

namespace App\Events;

use Illuminate\Broadcasting\InteractsWithSockets;
use Illuminate\Broadcasting\PrivateChannel;
use Illuminate\Contracts\Broadcasting\ShouldBroadcastNow;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

class SystemStateChanged implements ShouldBroadcastNow
{
    use Dispatchable, InteractsWithSockets, SerializesModels;

    public function __construct(
        public readonly int $userId,
        public readonly string $resource,
    ) {}

    public function broadcastOn(): array
    {
        return [
            new PrivateChannel("user.{$this->userId}"),
            new PrivateChannel('admin-dashboard'),
        ];
    }

    public function broadcastAs(): string
    {
        return 'state.updated';
    }

    public function broadcastWith(): array
    {
        return [
            'resource' => $this->resource,
            'user_id' => $this->userId,
            'occurred_at' => now()->toIso8601String(),
        ];
    }
}
