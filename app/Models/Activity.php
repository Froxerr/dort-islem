<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;

class Activity extends Model
{
    use HasFactory;

    protected $fillable = [
        'user_id',
        'type',
        'description',
        'data',
        'actor_id' // Aktiviteyi gerçekleştiren kullanıcı
    ];

    protected $casts = [
        'data' => 'array'
    ];

    /**
     * Aktivite tipleri
     */
    const TYPE_QUIZ = 'quiz';
    const TYPE_BADGE = 'badge';
    const TYPE_LEVEL = 'level';
    const TYPE_PROFILE_VIEW = 'profile_view';
    const TYPE_FRIENDSHIP = 'friendship';

    /**
     * Bu aktivitenin sahibi olan kullanıcı
     */
    public function user()
    {
        return $this->belongsTo(User::class);
    }

    /**
     * Bu aktiviteyi gerçekleştiren kullanıcı
     */
    public function actor()
    {
        return $this->belongsTo(User::class, 'actor_id');
    }

    /**
     * Profil görüntüleme aktivitesi ekle
     */
    public static function logProfileView($viewedUser, $viewerUser)
    {
        // Son 24 saatte aynı kullanıcı tarafından görüntülenmiş mi kontrol et
        $recentView = self::where('user_id', $viewedUser->id)
            ->where('actor_id', $viewerUser->id)
            ->where('type', self::TYPE_PROFILE_VIEW)
            ->where('created_at', '>=', now()->subHours(24))
            ->exists();

        // Eğer son 24 saatte görüntülenmemişse yeni kayıt ekle
        if (!$recentView) {
            return self::create([
                'user_id' => $viewedUser->id,
                'actor_id' => $viewerUser->id,
                'type' => self::TYPE_PROFILE_VIEW,
                'description' => "{$viewerUser->name} profilinizi görüntüledi",
                'data' => [
                    'viewer_name' => $viewerUser->name,
                    'viewer_username' => $viewerUser->username,
                    'viewer_level' => $viewerUser->level,
                ]
            ]);
        }

        return null;
    }
} 