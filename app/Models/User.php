<?php

namespace App\Models;

use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Laravel\Fortify\TwoFactorAuthenticatable;
use App\Traits\GeneratesUsername;
use Laravel\Scout\Searchable;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;

class User extends Authenticatable
{
    use HasFactory, Notifiable, TwoFactorAuthenticatable, GeneratesUsername;

    protected $fillable = [
        'username',
        'email',
        'password',
        'xp',
        'level',
        // Profile settings
        'name',
        'profile_image',
        'bio',
        // Account security
        'two_factor_enabled',
        'login_notifications',
        // Game preferences
        'default_difficulty_id',
        'favorite_topic_id',
        'auto_next_question',
        'show_correct_answers',
        'sound_effects',
        'theme',
        // Email notifications
        'email_achievements',
        'email_level_up',
        'email_weekly_summary',
        'email_reminders',
        // Push notifications
        'push_achievements',
        'push_level_up',
        'push_quiz_complete',
        // Privacy settings
        'profile_visibility',
        'show_stats',
        'show_achievements',
        'show_activity'
    ];

    protected $attributes = [
        'xp' => 0,
        'level' => 1
    ];

    protected $hidden = [
        'password',
        'remember_token',
        'two_factor_recovery_codes',
        'two_factor_secret',
    ];

    protected $casts = [
        'email_verified_at' => 'datetime',
        'password' => 'hashed',
        // Boolean settings
        'two_factor_enabled' => 'boolean',
        'login_notifications' => 'boolean',
        'auto_next_question' => 'boolean',
        'show_correct_answers' => 'boolean',
        'sound_effects' => 'boolean',
        'email_achievements' => 'boolean',
        'email_level_up' => 'boolean',
        'email_weekly_summary' => 'boolean',
        'email_reminders' => 'boolean',
        'push_achievements' => 'boolean',
        'push_level_up' => 'boolean',
        'push_quiz_complete' => 'boolean',
        'show_stats' => 'boolean',
        'show_achievements' => 'boolean',
        'show_activity' => 'boolean',
    ];

    /**
     * Kullanıcı adı oluştur
     */
    protected static function boot()
    {
        parent::boot();

        static::creating(function ($user) {
            if (!$user->username && $user->name) {
                $user->username = $user->generateUniqueUsername($user->name);
            }
        });
    }

    /**
     * Kullanıcı arama scopeları
     */
    public function scopeSearch($query, $searchTerm)
    {
        return $query->where(function($q) use ($searchTerm) {
            $q->where('name', 'LIKE', "%{$searchTerm}%")
              ->orWhere('username', 'LIKE', "%{$searchTerm}%");
        });
    }

    public function scopeWithMutualFriendsCount($query, $userId)
    {
        return $query->addSelect([
            'mutual_friends_count' => Friendship::selectRaw('COUNT(*)')
                ->whereIn('user_id', function($q) use ($userId) {
                    $q->select('friend_id')
                      ->from('friendships')
                      ->where('user_id', $userId)
                      ->where('status', 'accepted');
                })
                ->whereIn('friend_id', function($q) {
                    $q->select('friend_id')
                      ->from('friendships')
                      ->whereColumn('friendships.user_id', 'users.id')
                      ->where('status', 'accepted');
                })
                ->where('status', 'accepted')
        ]);
    }

    public function scopeWithFriendshipStatus($query, $userId)
    {
        return $query->addSelect([
            'friendship_status' => Friendship::selectRaw("
                CASE
                    WHEN user_id = {$userId} AND status = 'accepted' THEN 'friends'
                    WHEN friend_id = {$userId} AND status = 'accepted' THEN 'friends'
                    WHEN user_id = {$userId} AND status = 'pending' THEN 'pending_sent'
                    WHEN friend_id = {$userId} AND status = 'pending' THEN 'pending_received'
                    ELSE 'none'
                END
            ")
            ->whereRaw("(user_id = {$userId} AND friend_id = users.id) OR (friend_id = {$userId} AND user_id = users.id)")
            ->limit(1)
        ]);
    }

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

    public function defaultDifficulty()
    {
        return $this->belongsTo(DifficultyLevel::class, 'default_difficulty_id');
    }

    public function favoriteTopic()
    {
        return $this->belongsTo(Topic::class, 'favorite_topic_id');
    }

    /**
     * Generate 2FA QR Code
     */
    public function generateTwoFactorQrCode()
    {
        $google2fa = app('pragmarx.google2fa');

        if (!$this->two_factor_secret) {
            $this->forceFill([
                'two_factor_secret' => encrypt($google2fa->generateSecretKey()),
            ])->save();
        }

        return $google2fa->getQRCodeUrl(
            config('app.name'),
            $this->email,
            decrypt($this->two_factor_secret)
        );
    }

    /**
     * Verify 2FA code
     */
    public function verifyTwoFactorCode($code)
    {
        $google2fa = app('pragmarx.google2fa');

        return $google2fa->verifyKey(
            decrypt($this->two_factor_secret),
            $code
        );
    }

    /**
     * Enable 2FA
     */
    public function enableTwoFactorAuth()
    {
        $this->forceFill([
            'two_factor_enabled' => true,
        ])->save();
    }

    /**
     * Disable 2FA
     */
    public function disableTwoFactorAuth()
    {
        $this->forceFill([
            'two_factor_enabled' => false,
            'two_factor_secret' => null,
            'two_factor_recovery_codes' => null,
        ])->save();
    }

    /**
     * Kullanıcının belirli bir konudaki mevcut başarı serisini hesaplar
     */
    public function getCurrentStreak($topicId)
    {
        $recentSessions = $this->quizSessions()
            ->where('topic_id', $topicId)
            ->orderBy('created_at', 'desc')
            ->limit(10)
            ->get();

        $streak = 0;
        foreach ($recentSessions as $session) {
            $accuracy = ($session->correct_answers / $session->total_questions) * 100;
            if ($accuracy >= 70) { // 70% başarı oranı
                $streak++;
            } else {
                break;
            }
        }

        return $streak;
    }

    /**
     * Kullanıcının belirli bir konudaki toplam doğru cevap sayısını döndürür
     */
    public function getTotalCorrectAnswers($topicId = null)
    {
        $query = $this->quizSessions();

        if ($topicId) {
            $query->where('topic_id', $topicId);
        }

        return $query->sum('correct_answers');
    }

    /**
     * Kullanıcının belirli bir konudaki ortalama doğruluk oranını döndürür
     */
    public function getAverageAccuracy($topicId = null)
    {
        $query = $this->quizSessions();

        if ($topicId) {
            $query->where('topic_id', $topicId);
        }

        $sessions = $query->get();

        if ($sessions->isEmpty()) {
            return 0;
        }

        $totalAccuracy = $sessions->sum(function ($session) {
            return ($session->correct_answers / $session->total_questions) * 100;
        });

        return $totalAccuracy / $sessions->count();
    }

    /**
     * Kullanıcının aktiviteleri
     */
    public function activities()
    {
        return $this->hasMany(Activity::class);
    }

    /**
     * Aktivite ekle
     */
    public function addActivity($type, $description, $data = null)
    {
        return $this->activities()->create([
            'type' => $type,
            'description' => $description,
            'data' => $data
        ]);
    }

    // Arkadaşlık İlişkileri

    /**
     * Gönderilen arkadaşlık istekleri
     */
    public function sentFriendRequests()
    {
        return $this->hasMany(Friendship::class, 'user_id');
    }

    /**
     * Alınan arkadaşlık istekleri
     */
    public function receivedFriendRequests()
    {
        return $this->hasMany(Friendship::class, 'friend_id');
    }

    /**
     * Kabul edilmiş arkadaşlar (iki yönlü) - Basit versiyonu
     */
    public function friends()
    {
        // Daha basit ve stabil approach - Collection döndürür
        $friendIds = \Illuminate\Support\Facades\DB::table('friendships')
            ->where('status', 'accepted')
            ->where(function($query) {
                $query->where('user_id', $this->id)
                      ->orWhere('friend_id', $this->id);
            })
            ->get()
            ->flatMap(function($friendship) {
                return [$friendship->user_id, $friendship->friend_id];
            })
            ->filter(function($id) {
                return $id != $this->id;
            })
            ->unique()
            ->values();
            
        return User::whereIn('id', $friendIds);
    }

    /**
     * Arkadaş sayısını getir (performans için)
     */
    public function getFriendsCountAttribute()
    {
        return Friendship::where(function($query) {
            $query->where('user_id', $this->id)
                  ->orWhere('friend_id', $this->id);
        })->where('status', 'accepted')->count();
    }

    /**
     * Engellenen kullanıcılar
     */
    public function blockedUsers()
    {
        return $this->belongsToMany(User::class, 'friendships', 'user_id', 'friend_id')
                    ->wherePivot('status', 'blocked')
                    ->withTimestamps();
    }

    // Arkadaşlık Helper Metodları

    /**
     * Belirli bir kullanıcıyla arkadaş mı?
     */
    public function isFriendWith($userId)
    {
        return Friendship::betweenUsers($this->id, $userId)
                        ->accepted()
                        ->exists();
    }

    /**
     * Belirli bir kullanıcıdan bekleyen istek var mı?
     */
    public function hasPendingRequestFrom($userId)
    {
        return Friendship::where('user_id', $userId)
                        ->where('friend_id', $this->id)
                        ->pending()
                        ->exists();
    }

    /**
     * Belirli bir kullanıcıya istek gönderilmiş mi?
     */
    public function hasPendingRequestTo($userId)
    {
        return Friendship::where('user_id', $this->id)
                        ->where('friend_id', $userId)
                        ->pending()
                        ->exists();
    }

    /**
     * Belirli bir kullanıcıyı engellemiş mi?
     */
    public function hasBlockedUser($userId)
    {
        return Friendship::where('user_id', $this->id)
                        ->where('friend_id', $userId)
                        ->blocked()
                        ->exists();
    }

    /**
     * Ortak arkadaş sayısını getir
     */
    public function getMutualFriendsCount($userId)
    {
        $myFriends = $this->friends()->pluck('users.id');
        $theirFriends = User::find($userId)->friends()->pluck('users.id');

        return $myFriends->intersect($theirFriends)->count();
    }

    /**
     * Ortak arkadaşları getir
     */
    public function getMutualFriends($userId)
    {
        $myFriends = $this->friends()->pluck('users.id');
        $theirFriends = User::find($userId)->friends()->pluck('users.id');
        $mutualIds = $myFriends->intersect($theirFriends);

        return User::whereIn('id', $mutualIds)->get();
    }

    /**
     * Arkadaşlık durumunu getir
     */
    public function getFriendshipStatus($userId)
    {
        if ($this->id == $userId) {
            return 'self';
        }

        $friendship = Friendship::betweenUsers($this->id, $userId)->first();

        if (!$friendship) {
            return 'none'; // Arkadaşlık yok
        }

        if ($friendship->status === 'accepted') {
            return 'friends';
        }

        if ($friendship->status === 'blocked') {
            return 'blocked';
        }

        if ($friendship->status === 'pending') {
            if ($friendship->user_id == $this->id) {
                return 'pending_sent'; // Ben gönderdim
            } else {
                return 'pending_received'; // Bana gönderildi
            }
        }

        return 'none';
    }

    /**
     * Arkadaşlık isteği gönder
     */
    public function sendFriendRequest($userId)
    {
        // Zaten arkadaş mı kontrol et
        if ($this->isFriendWith($userId)) {
            return false;
        }

        // Zaten istek var mı kontrol et
        if ($this->hasPendingRequestTo($userId) || $this->hasPendingRequestFrom($userId)) {
            return false;
        }

        // Engellenmiş mi kontrol et
        if ($this->hasBlockedUser($userId)) {
            return false;
        }

        return Friendship::create([
            'user_id' => $this->id,
            'friend_id' => $userId,
            'status' => 'pending'
        ]);
    }

    /**
     * Bir kullanıcının birden çok sohbete katılımı olabilir.
     * 'conversation_participants' ara tablosu üzerinden ilişki kurulur.
     *
     * @return BelongsToMany
     */
    public function conversations(): BelongsToMany
    {
        return $this->belongsToMany(Conversation::class, 'conversation_participants');
    }

    /**
     * Bir kullanıcının gönderdiği tüm mesajlar.
     *
     * @return HasMany
     */
    public function messages(): HasMany
    {
        return $this->hasMany(Message::class);
    }
}
