<?php

namespace App\Models;

// use Illuminate\Contracts\Auth\MustVerifyEmail;
use App\Notifications\VerifyEmailNotification;
use Illuminate\Contracts\Auth\MustVerifyEmail;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Laravel\Sanctum\HasApiTokens;

class User extends Authenticatable implements MustVerifyEmail
{
    const USER_ROLE_SYSTEM_ADMIN = 2;
    const USER_ROLE_Customer = 1;

    use HasApiTokens;
    /** @use HasFactory<\Database\Factories\UserFactory> */
    use HasFactory, Notifiable;

    /**
     * The attributes that are mass assignable.
     *
     * @var array<int, string>
     */
    protected $fillable = [
        'name',
        'email',
        'password',
        'role',
        'employer',
    ];

    /**
     * The attributes that should be hidden for serialization.
     *
     * @var array<int, string>
     */
    protected $hidden = [
        'password',
        'remember_token',
    ];

    /**
     * Get the attributes that should be cast.
     *
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'email_verified_at' => 'datetime',
            'password' => 'hashed',
        ];
    }

    public function shops()
    {
        return $this->belongsToMany(Shop::class, 'shop_user');
    }
    public function shiftLabels()
    {
        return $this->hasMany(ShiftLabel::class);
    }
    // Relationship: Creator of the user
    public function creator()
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    // Relationship: Managers of the user
    public function managers()
    {
        return $this->belongsToMany(User::class, 'user_manager', 'user_id', 'manager_id');
    }

    // Relationship: Users managed by this user
    public function managedUsers()
    {
        return $this->belongsToMany(User::class, 'user_manager', 'manager_id', 'user_id');
    }
    public function sendEmailVerificationNotification()
    {
        $this->notify(new VerifyEmailNotification);
    }

}
