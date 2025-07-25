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
        'xp',
        'level'
    ];

    protected $attributes = [
        'xp' => 0,
        'level' => 1
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
        return $this->belongsToMany(Badge::class, 'user_badges')
                    ->withPivot('earned_at');
    }

    public function roles()
    {
        return $this->belongsToMany(Role::class, 'role_users');
    }

    public function quizSessions()
    {
        return $this->hasMany(QuizSession::class);
    }

    public function achievements()
    {
        return $this->belongsToMany(Achievement::class, 'user_achievements')
                    ->withPivot('progress', 'completed', 'completed_at')
                    ->withTimestamps();
    }

    /**
     * Kullanıcının belirli bir konudaki mevcut başarı serisini hesaplar
     */
    public function getCurrentStreak($topicId)
    {
        $sessions = $this->quizSessions()
            ->where('topic_id', $topicId)
            ->orderBy('created_at', 'desc')
            ->get();

        if ($sessions->isEmpty()) {
            return 0;
        }

        $streak = 0;
        $lastDate = null;

        foreach ($sessions as $session) {
            $sessionDate = $session->created_at->format('Y-m-d');
            
            if ($lastDate === null) {
                $lastDate = $session->created_at;
                $streak = 1;
                continue;
            }

            // Bir önceki test ile arasında bir gün fark var mı kontrol et
            if ($session->created_at->diffInDays($lastDate) === 1) {
                $streak++;
                $lastDate = $session->created_at;
            } else {
                // Seri bozuldu
                break;
            }
        }

        return $streak;
    }
}
