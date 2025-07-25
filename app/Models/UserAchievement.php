<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class UserAchievement extends Model
{
    protected $fillable = [
        'user_id',
        'achievement_id',
        'completed',
        'progress',
        'completed_at'
    ];

    protected $casts = [
        'progress' => 'integer',
        'completed_at' => 'datetime'
    ];

    /**
     * Bu başarımın sahibi olan kullanıcıyı getir
     */
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    /**
     * Bu kaydın ait olduğu başarımı getir
     */
    public function achievement(): BelongsTo
    {
        return $this->belongsTo(Achievement::class);
    }

    /**
     * Başarımın tamamlanıp tamamlanmadığını kontrol et
     */
    public function isCompleted(): bool
    {
        return !is_null($this->completed_at);
    }

    /**
     * Başarımı tamamla
     */
    public function complete(): void
    {
        if (!$this->isCompleted()) {
            $this->completed_at = now();
            $this->progress = 100;
            $this->save();
        }
    }

    /**
     * İlerlemeyi güncelle
     */
    public function updateProgress(int $progress): void
    {
        $this->progress = min(100, max(0, $progress));

        if ($this->progress === 100 && !$this->isCompleted()) {
            $this->complete();
        } else {
            $this->save();
        }
    }
}
