<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Role extends Model
{
    protected $table = 'roles';

    protected $fillable = [
        'name',
        'description'
    ];

    public $timestamps = false;

    public function users()
    {
        return $this->belongsToMany(User::class, 'role_user');
    }
}
