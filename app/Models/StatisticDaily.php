<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class StatisticDaily extends Model
{
    use HasFactory;

    public $timestamps = false;

    protected $table = 'statistics_daily';

    protected $fillable = [
        'user_id',
        'date',
        'alerts_sent',
        'alerts_delivered',
        'alerts_failed',
        'devices_online_avg',
        'delivery_rate',
    ];

    protected function casts(): array
    {
        return [
            'date' => 'date',
            'alerts_sent' => 'integer',
            'alerts_delivered' => 'integer',
            'alerts_failed' => 'integer',
            'devices_online_avg' => 'decimal:2',
            'delivery_rate' => 'decimal:2',
            'created_at' => 'datetime',
        ];
    }

    // Relationships
    public function user()
    {
        return $this->belongsTo(User::class);
    }
}
