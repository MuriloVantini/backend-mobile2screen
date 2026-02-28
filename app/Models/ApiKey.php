<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class ApiKey extends Model
{
    use HasFactory;

    public $timestamps = false;

    protected $fillable = [
        'user_id',
        'key_hash',
        'name',
        'last_used',
        'expires_at',
        'is_active',
    ];

    protected function casts(): array
    {
        return [
            'last_used' => 'datetime',
            'expires_at' => 'datetime',
            'is_active' => 'boolean',
            'created_at' => 'datetime',
        ];
    }

    // Relationships
    public function user()
    {
        return $this->belongsTo(User::class);
    }
}
