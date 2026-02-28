<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class UserSetting extends Model
{
    use HasFactory;

    protected $primaryKey = 'user_id';
    public $incrementing = false;

    protected $fillable = [
        'user_id',
        'notify_alert_failed',
        'notify_device_offline',
        'notify_weekly_report',
        'notify_device_connected',
        'notify_limit_reached',
        'notification_email',
        'notification_phone',
        'timezone',
        'language',
        'theme',
    ];

    protected function casts(): array
    {
        return [
            'notify_alert_failed' => 'boolean',
            'notify_device_offline' => 'boolean',
            'notify_weekly_report' => 'boolean',
            'notify_device_connected' => 'boolean',
            'notify_limit_reached' => 'boolean',
        ];
    }

    // Relationships
    public function user()
    {
        return $this->belongsTo(User::class);
    }
}
