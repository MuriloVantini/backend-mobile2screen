<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Plan extends Model
{
    use HasFactory;

    public $timestamps = false;

    protected $fillable = [
        'name',
        'max_devices',
        'max_alerts_per_month',
        'features',
        'price',
    ];

    protected function casts(): array
    {
        return [
            'features' => 'array',
            'price' => 'decimal:2',
            'created_at' => 'datetime',
        ];
    }

    // Relationships
    public function users()
    {
        return $this->hasMany(User::class);
    }
}
