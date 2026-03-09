<?php

namespace App\Models;

use App\Http\Resources\WebhookLogResource;
use Illuminate\Database\Eloquent\Attributes\UseResource;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

#[UseResource(WebhookLogResource::class)]
class WebhookLog extends Model
{
    use HasFactory;

    public $timestamps = false;

    protected $fillable = [
        'webhook_id',
        'event_type',
        'payload',
        'response_status',
        'response_body',
        'error_message',
    ];

    protected function casts(): array
    {
        return [
            'payload' => 'array',
            'response_status' => 'integer',
            'created_at' => 'datetime',
        ];
    }

    // Relationships
    public function webhook()
    {
        return $this->belongsTo(Webhook::class);
    }
}
