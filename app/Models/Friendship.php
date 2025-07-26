<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Friendship extends Model
{
    use HasFactory;

    protected $fillable = [
        'user_id',
        'friend_id', 
        'status',
        'accepted_at'
    ];

    protected $casts = [
        'accepted_at' => 'datetime',
    ];

    // İlişkiler
    
    /**
     * Arkadaşlık isteği gönderen kullanıcı
     */
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class, 'user_id');
    }

    /**
     * Arkadaşlık isteği alan kullanıcı
     */
    public function friend(): BelongsTo
    {
        return $this->belongsTo(User::class, 'friend_id');
    }

    // Scope'lar (Sorgu kolaylığı için)
    
    /**
     * Sadece bekleyen istekleri getir
     */
    public function scopePending($query)
    {
        return $query->where('status', 'pending');
    }

    /**
     * Sadece kabul edilmiş arkadaşlıkları getir
     */
    public function scopeAccepted($query)
    {
        return $query->where('status', 'accepted');
    }

    /**
     * Sadece engellenmiş kullanıcıları getir
     */
    public function scopeBlocked($query)
    {
        return $query->where('status', 'blocked');
    }

    /**
     * Belirli bir kullanıcının arkadaşlıklarını getir (iki yönlü)
     */
    public function scopeForUser($query, $userId)
    {
        return $query->where(function($q) use ($userId) {
            $q->where('user_id', $userId)
              ->orWhere('friend_id', $userId);
        });
    }

    /**
     * İki kullanıcı arasındaki arkadaşlığı getir
     */
    public function scopeBetweenUsers($query, $userId1, $userId2)
    {
        return $query->where(function($q) use ($userId1, $userId2) {
            $q->where(function($subQ) use ($userId1, $userId2) {
                $subQ->where('user_id', $userId1)
                     ->where('friend_id', $userId2);
            })->orWhere(function($subQ) use ($userId1, $userId2) {
                $subQ->where('user_id', $userId2)
                     ->where('friend_id', $userId1);
            });
        });
    }

    // Helper Metodlar

    /**
     * Arkadaşlığı kabul et
     */
    public function accept()
    {
        $this->update([
            'status' => 'accepted',
            'accepted_at' => now()
        ]);
    }

    /**
     * Arkadaşlığı reddet (sil)
     */
    public function reject()
    {
        $this->delete();
    }

    /**
     * Kullanıcıyı engelle
     */
    public function block()
    {
        $this->update([
            'status' => 'blocked',
            'accepted_at' => null
        ]);
    }

    /**
     * Bu arkadaşlıkta diğer kullanıcıyı getir
     */
    public function getOtherUser($currentUserId)
    {
        if ($this->user_id == $currentUserId) {
            return $this->friend;
        }
        return $this->user;
    }
}
