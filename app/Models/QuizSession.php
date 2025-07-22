<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;

class QuizSession extends Model
{
    use HasFactory;

    protected $table = 'QuizSession';

    protected $fillable = [
        'user_id',
        'topic_id',
        'difficulty_level_id',
        'score',
        'xp_earned',
        'total_questions',
        'correct_answers'
    ];

    public function user()
    {
        return $this->belongsTo(User::class);
    }

    public function topic()
    {
        return $this->belongsTo(Topic::class);
    }

    public function difficulty_level()
    {
        return $this->belongsTo(DifficultyLevel::class);
    }
}
