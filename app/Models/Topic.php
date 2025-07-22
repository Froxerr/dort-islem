<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Topic extends Model
{
    protected $table = 'topics';

    protected $fillable = [
        'name',
        'slug',
        'description',
        'icon_path',
        'is_active'
    ];

    public $timestamps = false;
    
    public function quizSessions()
    {
        return $this->hasMany(QuizSession::class);
    }
}
