<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;
class Conversation extends Model
{
    use HasFactory;

    /**
     * Bir sohbetin birden çok mesajı olabilir.
     *
     * @return HasMany
     */
    public function messages(): HasMany
    {
        return $this->hasMany(Message::class);
    }

    /**
     * Bir sohbetin en son mesajını almak için kullanışlı bir ilişki.
     * Bu, sohbet listesi arayüzlerinde son mesajı göstermek için çok yararlıdır.
     *
     * @return HasOne
     */
    public function latestMessage(): HasOne
    {
        return $this->hasOne(Message::class)->latest('id');
    }

    /**
     * Bir sohbetin birden çok katılımcısı (kullanıcısı) olabilir.
     * 'conversation_participants' ara tablosu üzerinden ilişki kurulur.
     *
     * @return BelongsToMany
     */
    public function participants(): BelongsToMany
    {
        return $this->belongsToMany(User::class, 'conversation_participants');
    }
}
