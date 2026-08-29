<?php

namespace App\Models;

// use Illuminate\Contracts\Auth\MustVerifyEmail;
use App\Http\Resources\UserResource;
use Illuminate\Database\Eloquent\Attributes\UseResource;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Laravel\Sanctum\HasApiTokens;

#[UseResource(UserResource::class)]
class User extends Authenticatable
{
    /** @use HasFactory<\Database\Factories\UserFactory> */
    use HasApiTokens, HasFactory, Notifiable, SoftDeletes;

    /**
     * The attributes that are mass assignable.
     *
     * @var list<string>
     */
    protected $fillable = [
        'name',
        'email',
        'password',
        'company',
        'phone',
        'profile_image_path',
        'plan_id',
        'status',
        'role',
        'last_active',
    ];

    /**
     * The attributes that should be hidden for serialization.
     *
     * @var list<string>
     */
    protected $hidden = [
        'password',
    ];

    /**
     * Get the attributes that should be cast.
     *
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'last_active' => 'datetime',
            'joined_at' => 'datetime',
            'password' => 'hashed',
        ];
    }

    // Relationships
    public function plan()
    {
        return $this->belongsTo(Plan::class);
    }

    public function devices()
    {
        return $this->hasMany(Device::class);
    }

    public function alerts()
    {
        return $this->hasMany(Alert::class);
    }

    public function deliveries()
    {
        return $this->hasManyThrough(AlertDelivery::class, Alert::class);
    }

    public function tags()
    {
        return $this->hasMany(Tag::class);
    }

    public function apiKeys()
    {
        return $this->hasMany(ApiKey::class);
    }

    public function userSessions()
    {
        return $this->hasMany(UserSession::class);
    }

    public function settings()
    {
        return $this->hasOne(UserSetting::class);
    }

    public function activityLogs()
    {
        return $this->hasMany(ActivityLog::class);
    }

    public function statisticsDaily()
    {
        return $this->hasMany(StatisticDaily::class);
    }
}
