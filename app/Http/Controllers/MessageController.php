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
     * Arkadaş listesini getir (mesaj göndermek için)
     */
    public function getFriends()
    {
        try {
            // Basit test için direkt DB query kullan
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
                ->get();
            
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
    public function getMessages($conversationId)
    {
        $conversation = Conversation::with(['messages.user', 'participants'])
            ->findOrFail($conversationId);

        // Kullanıcının bu sohbete katılım yetkisi var mı kontrol et
        if (!$conversation->participants->contains('id', Auth::id())) {
            return response()->json(['error' => 'Unauthorized'], 403);
        }

        return response()->json($conversation->messages);
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

        $conversation = Conversation::findOrFail($request->conversation_id);
        
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

        $message->load('user');

        // Pusher ile mesajı broadcast et (geçici olarak devre dışı)
        broadcast(new MessageSent($message))->toOthers();
        
        return response()->json($message);
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
        }

        return response()->json(['success' => true]);
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
     * Tüm okunmamış mesaj sayısını getir
     */
    public function getTotalUnreadCount()
    {
        $conversationIds = Auth::user()->conversations()->pluck('conversations.id');
        
        $count = Message::whereIn('conversation_id', $conversationIds)
            ->where('user_id', '!=', Auth::id())
            ->whereNull('read_at')
            ->count();

        return response()->json(['count' => $count]);
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

        // Typing event'ini broadcast et (geçici olarak devre dışı)
        broadcast(new UserTyping(Auth::user(), $request->conversation_id, $request->is_typing))->toOthers();

        return response()->json(['success' => true]);
    }
} 