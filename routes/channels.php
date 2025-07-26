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

Broadcast::channel('conversation.{conversationId}', function ($user, $conversationId) {
    // Debug logging
    \Log::info('Channel auth attempt', [
        'user_id' => $user->id,
        'conversation_id' => $conversationId,
        'channel' => "conversation.{$conversationId}"
    ]);
    
    // Kullanıcının bu conversation'a katılma yetkisi var mı kontrol et
    $conversation = \App\Models\Conversation::find($conversationId);
    
    if (!$conversation) {
        \Log::warning('Conversation not found', ['conversation_id' => $conversationId]);
        return false;
    }
    
    $hasAccess = $conversation->participants->contains('id', $user->id);
    
    \Log::info('Channel auth result', [
        'user_id' => $user->id,
        'conversation_id' => $conversationId,
        'has_access' => $hasAccess,
        'participants' => $conversation->participants->pluck('id')->toArray()
    ]);
    
    return $hasAccess;
}); 