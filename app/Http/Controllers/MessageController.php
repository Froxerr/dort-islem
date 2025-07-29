<?php

namespace App\Http\Controllers;

use App\Models\Conversation;
use App\Models\Message;
use App\Models\User;
use App\Events\MessageSent;
use App\Events\UserTyping;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class MessageController extends Controller
{
    /**
     * Kullanıcının sohbet listesini getir
     */
    public function index()
    {
        $conversations = Auth::user()->conversations()
            ->with(['participants' => function($query) {
                $query->where('user_id', '!=', Auth::id());
            }])
            ->with('latestMessage.user')
            ->orderBy('updated_at', 'desc')
            ->get();

        return response()->json($conversations);
    }

    /**
     * Arkadaş listesini getir (mesaj göndermek için) - Unread count ve sıralama ile
     */
    public function getFriends()
    {
        try {
            $currentUserId = Auth::id();

            $friendIds = \Illuminate\Support\Facades\DB::table('friendships')
                ->where('status', 'accepted')
                ->where(function($query) use ($currentUserId) {
                    $query->where('user_id', $currentUserId)
                          ->orWhere('friend_id', $currentUserId);
                })
                ->get()
                ->flatMap(function($friendship) use ($currentUserId) {
                    return $friendship->user_id == $currentUserId
                        ? [$friendship->friend_id]
                        : [$friendship->user_id];
                })
                ->unique()
                ->values();

            $friends = User::whereIn('id', $friendIds)
                ->select('id', 'name', 'username', 'profile_image')
                ->get()
                ->map(function($friend) use ($currentUserId) {
                    // Bu arkadaşla olan konuşmayı bul
                    $conversation = Conversation::whereHas('participants', function($query) use ($currentUserId) {
                        $query->where('user_id', $currentUserId);
                    })
                    ->whereHas('participants', function($query) use ($friend) {
                        $query->where('user_id', $friend->id);
                    })
                    ->whereDoesntHave('participants', function($query) use ($currentUserId, $friend) {
                        $query->whereNotIn('user_id', [$currentUserId, $friend->id]);
                    })
                    ->first();

                    $unreadCount = 0;
                    $lastMessageTime = null;

                    if ($conversation) {
                        // Bu arkadaştan gelen okunmamış mesaj sayısı
                        $unreadCount = Message::where('conversation_id', $conversation->id)
                            ->where('user_id', $friend->id)
                            ->whereNull('read_at')
                            ->count();

                        // Son mesaj zamanı
                        $lastMessage = Message::where('conversation_id', $conversation->id)
                            ->latest('created_at')
                            ->first();

                        $lastMessageTime = $lastMessage ? $lastMessage->created_at : null;
                    }

                    $friend->unread_count = $unreadCount;
                    $friend->last_message_at = $lastMessageTime;

                    return $friend;
                });

            // Son mesaj zamanına göre sırala (yeni mesaj atanlar üstte)
            $friends = $friends->sortByDesc(function($friend) {
                return $friend->last_message_at ? $friend->last_message_at->timestamp : 0;
            })->values();

            return response()->json($friends);
        } catch (\Exception $e) {
            \Log::error('Error in getFriends: ' . $e->getMessage());
            \Log::error('Stack trace: ' . $e->getTraceAsString());

            // Fallback - boş arkadaş listesi döndür
            return response()->json([]);
        }
    }

    /**
     * Belirli bir kullanıcı ile sohbet başlat veya mevcut sohbeti getir
     */
    public function getOrCreateConversation(Request $request)
    {
        $friendId = $request->input('friend_id');
        $currentUserId = Auth::id();

        // İki kullanıcı arasında mevcut sohbet var mı kontrol et
        $conversation = Conversation::whereHas('participants', function($query) use ($currentUserId) {
            $query->where('user_id', $currentUserId);
        })
        ->whereHas('participants', function($query) use ($friendId) {
            $query->where('user_id', $friendId);
        })
        ->whereDoesntHave('participants', function($query) use ($currentUserId, $friendId) {
            $query->whereNotIn('user_id', [$currentUserId, $friendId]);
        })
        ->first();

        // Eğer sohbet yoksa yeni bir tane oluştur
        if (!$conversation) {
            $conversation = DB::transaction(function () use ($currentUserId, $friendId) {
                $conversation = Conversation::create();

                // Katılımcıları ekle
                $conversation->participants()->attach([$currentUserId, $friendId]);

                return $conversation;
            });
        }

        // Sohbeti katılımcıları ve mesajları ile birlikte döndür
        $conversation->load([
            'participants', // TÜM participants'ları yükle (filter etme)
            'messages' => function ($query) {
                $query->orderBy('id', 'desc')->limit(30);
            },
            'messages.user'
        ]);

        // Debug: Conversation data
        \Log::info('Conversation created/loaded', [
            'conversation_id' => $conversation->id,
            'current_user_id' => $currentUserId,
            'friend_id' => $friendId,
            'participants' => $conversation->participants()->pluck('user_id')->toArray(),
            'all_participants' => $conversation->participants()->get()->toArray()
        ]);
        return response()->json($conversation);
    }

    /**
     * Belirli bir sohbetin mesajlarını getir
     */
    public function getMessages(Request $request, $conversationId)
    {
        // 1. Frontend'den gelen veriyi loglayalım
        $beforeMessageId = $request->query('before_message_id');
        Log::info('--- getMessages Tetiklendi ---');
        Log::info('Gelen before_message_id: ' . ($beforeMessageId ?: 'YOK'));

        $conversation = \App\Models\Conversation::findOrFail($conversationId);

        if (!$conversation->participants->contains('id', \Illuminate\Support\Facades\Auth::id())) {
            return response()->json(['error' => 'Unauthorized'], 403);
        }

        $messagesQuery = $conversation->messages()
            ->with('user')
            ->orderBy('id', 'desc');

        if ($beforeMessageId) {
            $messagesQuery->where('id', '<', $beforeMessageId);
        }

        $messagesQuery->limit(30);

        // 2. Laravel'in çalıştıracağı SQL sorgusunu ve parametrelerini loglayalım
        Log::info('Çalıştırılacak SQL: ' . $messagesQuery->toSql());
        Log::info('Sorgu Parametreleri: ', $messagesQuery->getBindings());

        $messages = $messagesQuery->get();

        // 3. Veritabanından dönen sonuçları loglayalım
        Log::info('Dönen Mesaj IDleri: ' . $messages->pluck('id')->toJson());
        Log::info('--- getMessages Tamamlandı ---');

        return response()->json($messages);
    }

    /**
     * Yeni mesaj gönder
     */
    public function sendMessage(Request $request)
    {
        $request->validate([
            'conversation_id' => 'required|exists:conversations,id',
            'body' => 'required|string|max:1000'
        ]);

        $conversation = Conversation::with('participants')->findOrFail($request->conversation_id);

        // Kullanıcının bu sohbete katılım yetkisi var mı kontrol et
        if (!$conversation->participants->contains('id', Auth::id())) {
            return response()->json(['error' => 'Unauthorized'], 403);
        }

        $message = Message::create([
            'conversation_id' => $request->conversation_id,
            'user_id' => Auth::id(),
            'body' => $request->body
        ]);

        // Conversation'ın updated_at'ini güncelle
        $conversation->touch();

        // User relationship'ini pre-load et
        $message->load('user');

        // Optimize: Diğer participant'ları conversation'dan al (ek query yok)
        $otherParticipants = $conversation->participants
            ->where('id', '!=', Auth::id());

        // Broadcast event'lerini optimize et
        try {
            // Pusher ile mesajı broadcast et - single event for conversation
            broadcast(new MessageSent($message));

            // Send single user-level event for friend list updates (more efficient)
            $participantIds = $otherParticipants->pluck('id')->toArray();
            if (!empty($participantIds)) {
                // Single broadcast event with multiple recipients is more efficient
                foreach ($otherParticipants as $participant) {
                    broadcast(new \App\Events\MessageReceived($message, $participant->id));
                }
            }
        } catch (\Exception $e) {
            // Broadcasting hatası varsa log'la ama response'u blokla
            \Log::error('Broadcasting error: ' . $e->getMessage());
        }

        return response()->json([
            'message' => $message,
            'success' => true
        ]);
    }

    /**
     * Mesajı okundu olarak işaretle
     */
    public function markAsRead(Request $request)
    {
        $request->validate([
            'message_id' => 'required|exists:messages,id'
        ]);

        $message = Message::findOrFail($request->message_id);

        // Kullanıcı bu mesajın sahibi değilse okundu işareti koyabilir
        if ($message->user_id != Auth::id()) {
            $message->update(['read_at' => now()]);

            // Real-time read status broadcast
            broadcast(new \App\Events\MessageRead($message))->toOthers();
        }

        return response()->json(['success' => true]);
    }

    /**
     * Batch mark messages as read - OPTIMIZED VERSION
     */
    public function markAsReadBatch(Request $request)
    {
        $request->validate([
            'message_ids' => 'required|array',
            'message_ids.*' => 'required|exists:messages,id'
        ]);

        $currentUserId = Auth::id();
        $messageIds = $request->message_ids;

        // Batch update messages that user can mark as read (not their own)
        $updatedCount = Message::whereIn('id', $messageIds)
            ->where('user_id', '!=', $currentUserId)
            ->whereNull('read_at')
            ->update(['read_at' => now()]);

        // Get updated messages for broadcasting
        if ($updatedCount > 0) {
            $updatedMessages = Message::whereIn('id', $messageIds)
                ->where('read_at', '!=', null)
                ->get();

            // Broadcast read status for each message
            foreach ($updatedMessages as $message) {
                broadcast(new \App\Events\MessageRead($message))->toOthers();
            }
        }

        return response()->json([
            'success' => true,
            'updated_count' => $updatedCount
        ]);
    }

    /**
     * Sohbetteki okunmamış mesaj sayısını getir
     */
    public function getUnreadCount($conversationId)
    {
        $count = Message::where('conversation_id', $conversationId)
            ->where('user_id', '!=', Auth::id())
            ->whereNull('read_at')
            ->count();

        return response()->json(['count' => $count]);
    }

    /**
     * Tüm okunmamış mesaj sayısını getir - Optimize edilmiş versiyon
     */
    public function getTotalUnreadCount()
    {
        try {
            $conversationIds = Auth::user()->conversations()->pluck('conversations.id');

            if ($conversationIds->isEmpty()) {
                return response()->json(['count' => 0]);
            }

            $count = Message::whereIn('conversation_id', $conversationIds)
                ->where('user_id', '!=', Auth::id())
                ->whereNull('read_at')
                ->count();

            return response()->json(['count' => $count]);
        } catch (\Exception $e) {
            \Log::error('Error getting unread count: ' . $e->getMessage());
            return response()->json(['count' => 0]);
        }
    }

    /**
     * Kullanıcı yazıyor durumunu broadcast et
     */
    public function typing(Request $request)
    {
        $request->validate([
            'conversation_id' => 'required|exists:conversations,id',
            'is_typing' => 'required|boolean'
        ]);

        $conversation = Conversation::findOrFail($request->conversation_id);

        // Kullanıcının bu sohbete katılım yetkisi var mı kontrol et
        if (!$conversation->participants->contains('id', Auth::id())) {
            return response()->json(['error' => 'Unauthorized'], 403);
        }

        try {
            broadcast(new \App\Events\UserTyping(Auth::user(), $request->conversation_id, $request->is_typing))->toOthers();
        } catch (\Exception $e) {
            \Log::error('Typing broadcast error: ' . $e->getMessage());
        }

        return response()->json(['success' => true]);
    }
}
