<?php

namespace App\Models;

use App\Http\Resources\AlertDeliveryResource;
use Illuminate\Database\Eloquent\Attributes\UseResource;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

#[UseResource(AlertDeliveryResource::class)]
class AlertDelivery extends Model
{
    use HasFactory;

    public $timestamps = false;

    protected $fillable = [
        'alert_id',
        'device_id',
        'status',
        'delivered_at',
        'acknowledged_at',
        'dismissed_at',
        'error_message',
        'retry_count',
    ];

    protected function casts(): array
    {
        return [
            'delivered_at' => 'datetime',
            'acknowledged_at' => 'datetime',
            'dismissed_at' => 'datetime',
            'retry_count' => 'integer',
            'created_at' => 'datetime',
        ];
    }

    // Relationships
    public function alert()
    {
        return $this->belongsTo(Alert::class);
    }

    public function device()
    {
        return $this->belongsTo(Device::class);
    }
}
