<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class DifficultyLevel extends Model
{
    protected $table = 'difficulty_levels';

    protected $fillable = [
        'name',
        'xp_multiplier'
    ];

    public $timestamps = false;

    public function quizSessions()
    {
        return $this->hasMany(QuizSession::class);
    }
} 