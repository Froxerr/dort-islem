<?php

namespace App\Models;

use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Illuminate\Database\Eloquent\Factories\HasFactory;

class User extends Authenticatable
{
    use HasFactory, Notifiable;
    protected $fillable = [
        'username',
        'email',
        'password',
        'level',
        'xp'
    ];

    protected $hidden = [
        'password',
        'remember_token',
    ];

    protected $casts = [
        'email_verified_at' => 'datetime',
        'password' => 'hashed',
    ];

    public function badges()
    {
        return $this->belongsToMany(Badge::class, 'user_badges');
    }

    public function roles()
    {
        return $this->belongsToMany(Role::class, 'role_users');
    }

    public function quizSessions()
    {
        return $this->hasMany(quizSession::class);
    }
}
