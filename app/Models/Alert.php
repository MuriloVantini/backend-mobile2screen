<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Alert extends Model
{
    use HasFactory;

    public $timestamps = false;

    protected $fillable = [
        'user_id',
        'title',
        'message',
        'type',
        'duration_seconds',
        'priority',
        'sent_at',
        'expires_at',
    ];

    protected function casts(): array
    {
        return [
            'priority' => 'integer',
            'duration_seconds' => 'integer',
            'sent_at' => 'datetime',
            'expires_at' => 'datetime',
            'created_at' => 'datetime',
        ];
    }

    // Relationships
    public function user()
    {
        return $this->belongsTo(User::class);
    }

    public function tags()
    {
        return $this->belongsToMany(Tag::class, 'alert_tags')->withPivot('created_at');
    }

    public function deliveries()
    {
        return $this->hasMany(AlertDelivery::class);
    }
}
