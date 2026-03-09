<?php

namespace App\Models;

use App\Http\Resources\TagResource;
use Illuminate\Database\Eloquent\Attributes\UseResource;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

#[UseResource(TagResource::class)]
class Tag extends Model
{
    use HasFactory;

    public $timestamps = false;

    protected $fillable = [
        'user_id',
        'name',
        'color',
    ];

    protected function casts(): array
    {
        return [
            'created_at' => 'datetime',
        ];
    }

    // Relationships
    public function user()
    {
        return $this->belongsTo(User::class);
    }

    public function devices()
    {
        return $this->belongsToMany(Device::class, 'device_tags')->withPivot('created_at');
    }

    public function alerts()
    {
        return $this->belongsToMany(Alert::class, 'alert_tags')->withPivot('created_at');
    }
}
