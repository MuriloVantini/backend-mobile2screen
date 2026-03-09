<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class Device extends Model
{
    use HasFactory, SoftDeletes;

    protected $fillable = [
        'user_id',
        'name',
        'type',
        'location',
        'ip_address',
        'mac_address',
        'is_online',
        'last_seen',
        'connection_token',
        'metadata',
    ];

    protected function casts(): array
    {
        return [
            'is_online' => 'boolean',
            'last_seen' => 'datetime',
            'metadata' => 'array',
        ];
    }

    // Relationships
    public function user()
    {
        return $this->belongsTo(User::class);
    }

    public function tags()
    {
        return $this->belongsToMany(Tag::class, 'device_tags')->withPivot('created_at');
    }

    public function alertDeliveries()
    {
        return $this->hasMany(AlertDelivery::class);
    }
}
