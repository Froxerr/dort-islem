<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class BadgeTrigger extends Model
{
    protected $fillable = [
        'badge_id',
        'trigger_type',
        'required_score',
        'required_count',
        'topic_id',
        'description'
    ];

    protected $casts = [
        'required_score' => 'integer',
        'required_count' => 'integer'
    ];

    /**
     * Tetikleyicinin bağlı olduğu rozeti getir
     */
    public function badge(): BelongsTo
    {
        return $this->belongsTo(Badge::class);
    }

    /**
     * Tetikleyicinin bağlı olduğu konuyu getir (eğer varsa)
     */
    public function topic(): BelongsTo
    {
        return $this->belongsTo(Topic::class);
    }

    /**
     * Tetikleyici türlerini tanımla
     */
    public const TRIGGER_TYPES = [
        'required_score' => 'Gerekli Skor',
        'required_count' => 'Gerekli Sayı',
        'streak' => 'Başarı Serisi'
    ];

    /**
     * Tetikleyicinin şartlarının sağlanıp sağlanmadığını kontrol et
     */
    public function checkConditions(int $score, int $count, ?int $topicCount = null, ?int $streakCount = null): bool
    {
        if ($this->trigger_type === 'required_score') {
            return $score >= $this->required_score;
        }

        if ($this->trigger_type === 'required_count') {
            if ($this->topic_id) {
                return $topicCount >= $this->required_count;
            }
            return $count >= $this->required_count;
        }

        if ($this->trigger_type === 'streak' && $streakCount !== null) {
            return $streakCount >= $this->required_count;
        }

        return false;
    }

    /**
     * Tetikleyici açıklamasını oluştur
     */
    public function getRequirementDescription(): string
    {
        if ($this->trigger_type === 'required_score') {
            return "En az {$this->required_score} puan kazan";
        }

        if ($this->trigger_type === 'required_count') {
            if ($this->topic_id) {
                $topicName = $this->topic->name ?? 'belirli konuda';
                return "{$topicName} konusunda en az {$this->required_count} doğru cevap ver";
            }
            return "Toplamda en az {$this->required_count} doğru cevap ver";
        }

        if ($this->trigger_type === 'streak') {
            if ($this->topic_id) {
                $topicName = $this->topic->name ?? 'belirli konuda';
                return "{$topicName} konusunda üst üste {$this->required_count} başarılı test tamamla";
            }
            return "Üst üste {$this->required_count} başarılı test tamamla";
        }

        return $this->description ?? 'Özel gereksinim';
    }
}
