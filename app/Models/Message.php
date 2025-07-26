<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Message extends Model
{
    use HasFactory;

    /**
     * Mass assignment (toplu atama) için izin verilen alanlar.
     *
     * @var array<int, string>
     */
    protected $fillable = [
        'conversation_id',
        'user_id',
        'body',
        'read_at',
    ];

    /**
     * Tarih formatına dönüştürülecek alanlar.
     *
     * @var array<int, string>
     */
    protected $casts = [
        'read_at' => 'datetime',
    ];

    /**
     * Bir mesajın ait olduğu sohbet.
     *
     * @return BelongsTo
     */
    public function conversation(): BelongsTo
    {
        return $this->belongsTo(Conversation::class);
    }

    /**
     * Bir mesajı gönderen kullanıcı.
     *
     * @return BelongsTo
     */
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }
}
