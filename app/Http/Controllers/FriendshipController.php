<?php

namespace App\Http\Controllers;

use App\Models\User;
use App\Models\Friendship;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema; // Added this import for Schema::hasTable
use App\Models\Activity; // Added this import for Activity model

class FriendshipController extends Controller
{
    /**
     * Ana arkadaş sayfası - Arkadaş listesi
     */
    public function index()
    {
        $user = Auth::user();
        
        // Arkadaşları getir (pivot data ile)
        $friends = collect();
        
        // Ben gönderdiğim kabul edilmiş istekler
        $sentFriends = $user->belongsToMany(User::class, 'friendships', 'user_id', 'friend_id')
                           ->wherePivot('status', 'accepted')
                           ->withPivot('accepted_at', 'created_at')
                           ->get();
        
        // Bana gönderilen kabul edilmiş istekler  
        $receivedFriends = $user->belongsToMany(User::class, 'friendships', 'friend_id', 'user_id')
                               ->wherePivot('status', 'accepted')
                               ->withPivot('accepted_at', 'created_at')
                               ->get();
        
        $friends = $sentFriends->merge($receivedFriends)->unique('id');
        
        // Carbon parse accepted_at
        $friends = $friends->map(function($friend) {
            if ($friend->pivot && $friend->pivot->accepted_at) {
                $friend->pivot->accepted_at = \Carbon\Carbon::parse($friend->pivot->accepted_at);
            }
            return $friend;
        });

        // Bekleyen istekleri getir
        $pendingRequests = Friendship::where('friend_id', $user->id)
                                   ->where('status', 'pending')
                                   ->with('user')
                                   ->get();

        // Gönderilen istekleri say
        $sentRequestsCount = Friendship::where('user_id', $user->id)
                                     ->where('status', 'pending')
                                     ->count();

        // İstatistikler
        $stats = [
            'friends_count' => $friends->count(),
            'pending_requests_count' => $pendingRequests->count(),
            'sent_requests_count' => $sentRequestsCount
        ];

        return view('profile.friends.index', [
            'friends' => $friends,
            'pendingRequests' => $pendingRequests,
            'stats' => $stats
        ]);
    }

    /**
     * Arkadaş arama sayfası
     */
    public function search(Request $request)
    {
        $user = Auth::user();
        $query = $request->get('q', '');
        $searchResults = collect();
        $suggestedFriends = collect();

        if (!empty($query)) {
            // Arama sonuçları - optimize edilmiş sorgu
            $searchResults = User::where('id', '!=', $user->id)
                                ->search($query)
                                ->withMutualFriendsCount($user->id)
                                ->withFriendshipStatus($user->id)
                                ->orderByRaw('
                                    CASE 
                                        WHEN name LIKE ? THEN 1
                                        WHEN username LIKE ? THEN 2
                                        WHEN name LIKE ? THEN 3
                                        WHEN username LIKE ? THEN 4
                                        ELSE 5
                                    END
                                ', [
                                    $query . '%',    // Tam baştan eşleşme
                                    $query . '%',    // Tam baştan eşleşme
                                    '%' . $query . '%', // İçinde geçme
                                    '%' . $query . '%', // İçinde geçme
                                ])
                                ->orderBy('mutual_friends_count', 'desc') // Ortak arkadaşı çok olanlar önce
                                ->limit(20)
                                ->get();
        } else {
            // Önerilen arkadaşlar - optimize edilmiş sorgu
            $suggestedFriends = $this->getSuggestedFriends($user);
        }

        // AJAX isteği için sadece sonuçları döndür
        if ($request->ajax()) {
            return view('profile.friends.partials.search-results', [
                'searchResults' => $searchResults,
                'query' => $query
            ])->render();
        }

        return view('profile.friends.search', compact('searchResults', 'suggestedFriends', 'query'));
    }

    /**
     * Kullanıcı profilini görüntüle
     */
    public function viewProfile($id)
    {
        $user = Auth::user();
        $targetUser = User::findOrFail($id);
        
        if ($user->id === $targetUser->id) {
            return redirect()->route('profile.details');
        }

        // Profil görünürlüğü kontrolü
        $canViewProfile = $this->canViewProfile($user, $targetUser);
        
        if (!$canViewProfile) {
            return view('profile.friends.profile-private', [
                'user' => $targetUser,
                'reason' => $this->getPrivacyReason($targetUser)
            ]);
        }

        $friendshipStatus = $user->getFriendshipStatus($targetUser->id);
        $mutualFriendsCount = $user->getMutualFriendsCount($targetUser->id);

        // İstatistik görünürlüğü kontrolü
        $showStats = $targetUser->show_stats && $this->canViewStats($user, $targetUser);
        $showAchievements = $targetUser->show_achievements && $this->canViewAchievements($user, $targetUser);
        $showActivity = $targetUser->show_activity && $this->canViewActivity($user, $targetUser);

        $stats = [];
        if ($showStats) {
            $stats = [
                'totalTests' => $targetUser->quizSessions()->count(),
                'averageAccuracy' => round($targetUser->getAverageAccuracy()),
                'highestScore' => $targetUser->quizSessions()->max('score') ?? 0
            ];
        }

        // Rozetleri al (sadece gösterme izni varsa)
        $badges = collect();
        if ($showAchievements) {
            $badges = $targetUser->badges()
                               ->orderBy('earned_at', 'desc')
                               ->get();

            \Log::info('Kullanıcı rozetleri alındı', [
                'user_id' => $targetUser->id,
                'badge_count' => $badges->count(),
                'badges' => $badges->map(function($badge) {
                    return [
                        'id' => $badge->id,
                        'name' => $badge->name,
                        'icon_filename' => $badge->icon_filename,
                        'earned_at' => $badge->earned_at
                    ];
                })
            ]);
        }

        // Son quiz sonuçları (sadece aktivite gösterme izni varsa)
        $recentQuizzes = collect();
        if ($showActivity) {
            $recentQuizzes = $targetUser->quizSessions()
                                       ->with('difficultyLevel')
                                       ->orderBy('created_at', 'desc')
                                       ->limit(10)
                                       ->get()
                                       ->map(function($quiz) {
                                           $incorrectAnswers = $quiz->total_questions - $quiz->correct_answers;
                                           $accuracy = $quiz->total_questions > 0 
                                               ? round(($quiz->correct_answers / $quiz->total_questions) * 100) 
                                               : 0;

                                           return [
                                               'date' => $quiz->created_at,
                                               'difficulty' => $quiz->difficultyLevel->name,
                                               'correct_answers' => $quiz->correct_answers,
                                               'wrong_answers' => $incorrectAnswers,
                                               'score' => $quiz->score,
                                               'accuracy' => $accuracy
                                           ];
                                       });
        }

        return view('profile.friends.view-profile', [
            'user' => $targetUser,
            'friendshipStatus' => $friendshipStatus,
            'mutualFriendsCount' => $mutualFriendsCount,
            'stats' => $stats,
            'badges' => $badges,
            'recentQuizzes' => $recentQuizzes,
            'showStats' => $showStats,
            'showAchievements' => $showAchievements,
            'showActivity' => $showActivity
        ]);
    }
    
    /**
     * Profil görüntüleme izni kontrolü
     */
    private function canViewProfile($viewer, $targetUser)
    {
        // Profil görünürlük ayarını kontrol et
        switch ($targetUser->profile_visibility) {
            case 'public':
                return true;
                
            case 'friends':
                return $viewer->isFriendWith($targetUser->id);
                
            case 'private':
                return false;
                
            default:
                return true; // Default: public
        }
    }
    
    /**
     * İstatistik görüntüleme izni kontrolü
     */
    private function canViewStats($viewer, $targetUser)
    {
        if ($targetUser->profile_visibility === 'private') {
            return false;
        }
        
        if ($targetUser->profile_visibility === 'friends') {
            return $viewer->isFriendWith($targetUser->id);
        }
        
        return true;
    }
    
    /**
     * Başarım görüntüleme izni kontrolü
     */
    private function canViewAchievements($viewer, $targetUser)
    {
        if ($targetUser->profile_visibility === 'private') {
            return false;
        }
        
        if ($targetUser->profile_visibility === 'friends') {
            return $viewer->isFriendWith($targetUser->id);
        }
        
        return true;
    }
    
    /**
     * Aktivite görüntüleme izni kontrolü
     */
    private function canViewActivity($viewer, $targetUser)
    {
        if ($targetUser->profile_visibility === 'private') {
            return false;
        }
        
        if ($targetUser->profile_visibility === 'friends') {
            return $viewer->isFriendWith($targetUser->id);
        }
        
        return true;
    }
    
    /**
     * Gizlilik sebebini açıkla
     */
    private function getPrivacyReason($targetUser)
    {
        switch ($targetUser->profile_visibility) {
            case 'friends':
                return 'Bu kullanıcı profilini sadece arkadaşlarına gösteriyor.';
            case 'private':
                return 'Bu kullanıcı profilini gizli tutmayı tercih ediyor.';
            default:
                return 'Bu profil şu anda görüntülenemiyor.';
        }
    }

    /**
     * Arkadaşlık isteği gönder
     */
    public function sendRequest(Request $request)
    {
        $request->validate([
            'user_id' => 'required|exists:users,id'
        ]);

        $user = Auth::user();
        $targetUserId = $request->user_id;

        // Kendine istek göndermeyi engelle
        if ($user->id == $targetUserId) {
            return response()->json([
                'success' => false,
                'message' => 'Kendinize arkadaşlık isteği gönderemezsiniz.'
            ]);
        }

        // Arkadaşlık isteği gönder
        $friendship = $user->sendFriendRequest($targetUserId);

        if ($friendship) {
            // Real-time bildirim gönder
            $targetUser = User::find($targetUserId);
            broadcast(new \App\Events\FriendRequestReceived($user, $friendship))->toOthers();

            return response()->json([
                'success' => true,
                'message' => 'Arkadaşlık isteği gönderildi!'
            ]);
        }

        return response()->json([
            'success' => false,
            'message' => 'Arkadaşlık isteği gönderilemedi. Zaten arkadaş olabilirsiniz veya bekleyen bir istek var.'
        ]);
    }

    /**
     * Arkadaşlık isteğini kabul et
     */
    public function acceptRequest(Request $request)
    {
        $request->validate([
            'friendship_id' => 'required|exists:friendships,id'
        ]);

        $user = Auth::user();
        $friendship = Friendship::where('id', $request->friendship_id)
                               ->where('friend_id', $user->id)
                               ->pending()
                               ->first();

        if (!$friendship) {
            return response()->json([
                'success' => false,
                'message' => 'Geçerli bir arkadaşlık isteği bulunamadı.'
            ]);
        }

        $friendship->accept();

        // İsteği gönderen kullanıcıyı getir
        $sender = User::find($friendship->user_id);
        
        // Real-time event gönder (her iki kullanıcıya da)
        broadcast(new \App\Events\FriendRequestAccepted($user, $sender, $friendship))->toOthers();

        return response()->json([
            'success' => true,
            'message' => 'Arkadaşlık isteği kabul edildi!'
        ]);
    }

    /**
     * Arkadaşlık isteğini reddet
     */
    public function rejectRequest(Request $request)
    {
        $request->validate([
            'friendship_id' => 'required|exists:friendships,id'
        ]);

        $user = Auth::user();
        $friendship = Friendship::where('id', $request->friendship_id)
                               ->where('friend_id', $user->id)
                               ->pending()
                               ->first();

        if (!$friendship) {
            return response()->json([
                'success' => false,
                'message' => 'Geçerli bir arkadaşlık isteği bulunamadı.'
            ]);
        }

        $friendship->reject();

        return response()->json([
            'success' => true,
            'message' => 'Arkadaşlık isteği reddedildi.'
        ]);
    }

    /**
     * Kullanıcıyı engelle
     */
    public function blockUser(Request $request)
    {
        $request->validate([
            'user_id' => 'required|exists:users,id'
        ]);

        $user = Auth::user();
        $targetUserId = $request->user_id;

        // Mevcut arkadaşlığı bul veya oluştur
        $friendship = Friendship::betweenUsers($user->id, $targetUserId)->first();

        if ($friendship) {
            $friendship->block();
        } else {
            Friendship::create([
                'user_id' => $user->id,
                'friend_id' => $targetUserId,
                'status' => 'blocked'
            ]);
        }

        return response()->json([
            'success' => true,
            'message' => 'Kullanıcı engellendi.'
        ]);
    }

    /**
     * Arkadaşı kaldır
     */
    public function removeFriend(Request $request)
    {
        $request->validate([
            'user_id' => 'required|exists:users,id'
        ]);

        $user = Auth::user();
        $targetUserId = $request->user_id;

        $friendship = Friendship::betweenUsers($user->id, $targetUserId)
                               ->accepted()
                               ->first();

        if (!$friendship) {
            return response()->json([
                'success' => false,
                'message' => 'Bu kullanıcıyla arkadaşlık bulunamadı.'
            ]);
        }

        $friendship->delete();

        return response()->json([
            'success' => true,
            'message' => 'Arkadaş kaldırıldı.'
        ]);
    }

    /**
     * Önerilen arkadaşları getir
     */
    private function getSuggestedFriends($user, $limit = 10)
    {
        // Kullanıcının arkadaşlarının arkadaşları (ortak arkadaş mantığı)
        $friendIds = $user->friends()->pluck('users.id');
        $excludeIds = $friendIds->push($user->id); // Kendini ve mevcut arkadaşları hariç tut

        // Engellenen kullanıcıları da hariç tut
        $blockedIds = $user->blockedUsers()->pluck('users.id');
        $excludeIds = $excludeIds->merge($blockedIds);

        // Bekleyen istekleri de hariç tut
        $pendingIds = $user->sentFriendRequests()->pending()->pluck('friend_id')
                          ->merge($user->receivedFriendRequests()->pending()->pluck('user_id'));
        $excludeIds = $excludeIds->merge($pendingIds);

        // Ortak arkadaşı olan kullanıcıları bul - optimize edilmiş sorgu
        return User::whereNotIn('id', $excludeIds)
                  ->withMutualFriendsCount($user->id)
                  ->withFriendshipStatus($user->id)
                  ->having('mutual_friends_count', '>', 0)
                  ->orderBy('mutual_friends_count', 'desc')
                  ->limit($limit)
                  ->get();
    }
}
