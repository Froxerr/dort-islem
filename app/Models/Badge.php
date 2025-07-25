<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Badge extends Model
{
    protected $table = 'badges';

    protected $fillable = [
        'name',
        'description',
        'icon_filename',
        'type',
        'achievement_id'
    ];

    public function users()
    {
        return $this->belongsToMany(User::class, 'user_badges')
                    ->withPivot('earned_at');
    }

    public function achievement(): BelongsTo
    {
        return $this->belongsTo(Achievement::class);
    }

    public function triggers(): HasMany
    {
        return $this->hasMany(BadgeTrigger::class);
    }

    // Topic'e ait rozet mi kontrol et
    public function isForTopic($topicId): bool
    {
        return $this->triggers()
            ->where('topic_id', $topicId)
            ->exists();
    }
}
