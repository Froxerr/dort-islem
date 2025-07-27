<?php

use Illuminate\Support\Facades\Broadcast;

/*
|--------------------------------------------------------------------------
| Broadcast Channels
|--------------------------------------------------------------------------
|
| Here you may register all of the event broadcasting channels that your
| application supports. The given channel authorization callbacks are
| used to check if an authenticated user can listen to the channel.
|
*/

Broadcast::channel('App.Models.User.{id}', function ($user, $id) {
    return (int) $user->id === (int) $id;
});

// User notifications channel (arkadaşlık daveti vs.)
Broadcast::channel('user.{userId}', function ($user, $userId) {
    return (int) $user->id === (int) $userId;
});

Broadcast::channel('conversation.{conversationId}', function ($user, $conversationId) {
    // Kullanıcının bu conversation'a katılma yetkisi var mı kontrol et
    $conversation = \App\Models\Conversation::find($conversationId);
    
    if (!$conversation) {
        return false;
    }
    
    return $conversation->participants->contains('id', $user->id);
}); 