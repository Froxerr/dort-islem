<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Level extends Model
{
    use HasFactory;

    protected $table = 'levels';

    protected $fillable = [
        'id',
        'xp_required_for_next_level'
    ];

    protected $casts = [
        'xp_required_for_next_level' => 'integer'
    ];

    public function users()
    {
        return $this->hasMany(User::class);
    }
}
