<?php

namespace App\Events;

use App\Models\AlertDelivery;
use Illuminate\Broadcasting\InteractsWithSockets;
use Illuminate\Broadcasting\PrivateChannel;
use Illuminate\Contracts\Broadcasting\ShouldBroadcastNow;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

class AlertAvailable implements ShouldBroadcastNow
{
    use Dispatchable, InteractsWithSockets, SerializesModels;

    public function __construct(public AlertDelivery $delivery) {}

    public function broadcastOn(): array
    {
        return [
            new PrivateChannel("device.{$this->delivery->device_id}"),
        ];
    }

    public function broadcastAs(): string
    {
        return 'alert.available';
    }

    public function broadcastWith(): array
    {
        return [
            'delivery_id' => $this->delivery->id,
        ];
    }
}
