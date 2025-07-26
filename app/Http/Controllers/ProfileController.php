<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use App\Models\User;
use App\Models\QuizSession;
use App\Models\Badge;
use Illuminate\Support\Facades\Log;
use App\Models\Topic;
use App\Models\Achievement;

class ProfileController extends Controller
{
    public function hub()
    {
        return view('profile.profile-hub');
    }

    public function show()
    {
        try {
            $user = Auth::user();

            if (!$user) {
                return redirect()->route('login');
            }

            Log::info('Profil görüntüleniyor', ['user_id' => $user->id]);

            // İstatistikleri hesapla
            $stats = QuizSession::where('user_id', $user->id)
                ->select(
                    DB::raw('COUNT(*) as total_tests'),
                    DB::raw('AVG(correct_answers * 100.0 / total_questions) as average_accuracy'),
                    DB::raw('SUM(xp_earned) as total_xp'),
                    DB::raw('MAX(score) as highest_score')
                )
                ->first();

            // İstatistik değerlerini hazırla
            $totalTests = $stats->total_tests ?? 0;
            $averageAccuracy = round($stats->average_accuracy ?? 0, 1);
            $totalXP = $stats->total_xp ?? 0;
            $highestScore = $stats->highest_score ?? 0;

            // Sonraki seviye için gereken XP'yi hesapla
            $nextLevel = DB::table('levels')
                ->where('xp_required_for_next_level', '>', $user->xp)
                ->orderBy('xp_required_for_next_level', 'asc')
                ->first();

            $currentLevelXP = DB::table('levels')
                ->where('level', $user->level)
                ->value('xp_required_for_next_level') ?? 0;

            // XP progress yüzdesini hesapla
            $xpProgressPercentage = 0;
            $xpNeeded = 0;
            if ($nextLevel) {
                $xpForThisLevel = $nextLevel->xp_required_for_next_level - $currentLevelXP;
                $xpGained = $user->xp - $currentLevelXP;
                $xpProgressPercentage = ($xpGained / max($xpForThisLevel, 1)) * 100;
                $xpNeeded = $nextLevel->xp_required_for_next_level - $user->xp;
            }

            // Kullanıcının rozetlerini al
            $userBadges = DB::table('user_badges')
                ->join('badges', 'badges.id', '=', 'user_badges.badge_id')
                ->where('user_badges.user_id', $user->id)
                ->select(
                    'badges.id',
                    'badges.name',
                    'badges.description',
                    'badges.icon_filename as image', // icon_filename'i image olarak al
                    'user_badges.user_id',
                    'user_badges.badge_id',
                    'user_badges.earned_at',
                    DB::raw('CASE WHEN user_badges.earned_at >= NOW() - INTERVAL 24 HOUR THEN 1 ELSE 0 END as is_new')
                )
                ->get();

            Log::info('Kullanıcı rozetleri yüklendi', [
                'user_id' => $user->id,
                'badge_count' => $userBadges->count(),
                'new_badges' => $userBadges->where('is_new', 1)->count()
            ]);

            // Son aktiviteleri al (son 2 quiz)
            $recentActivities = QuizSession::with(['topic', 'difficultyLevel'])
                ->where('user_id', $user->id)
                ->orderBy('created_at', 'desc')
                ->limit(2) // 5'ten 2'ye değiştirildi
                ->get()
                ->map(function ($session) {
                    $topicName = $session->topic ? $session->topic->name : 'Bilinmeyen Konu';
                    $difficultyName = $session->difficultyLevel ? $session->difficultyLevel->name : 'Bilinmeyen Zorluk';

                    return (object) [
                        'description' => sprintf(
                            '%s konusunda %s zorlukta test tamamlandı. %d XP kazanıldı!',
                            $topicName,
                            $difficultyName,
                            $session->xp_earned
                        ),
                        'created_at' => $session->created_at
                    ];
                });

            Log::info('Profil verileri hazırlandı', [
                'user_id' => $user->id,
                'total_tests' => $totalTests,
                'average_accuracy' => $averageAccuracy
            ]);

            return view('profile.profile', compact(
                'user',
                'xpProgressPercentage',
                'xpNeeded',
                'userBadges',
                'recentActivities',
                'totalTests',
                'averageAccuracy',
                'totalXP',
                'highestScore'
            ));

        } catch (\Exception $e) {
            dd($e->getMessage());
            Log::error('Profil görüntüleme hatası', [
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ]);

            return redirect()
                ->back()
                ->with('error', 'Profil bilgileri yüklenirken bir hata oluştu.');
        }
    }

    public function achievements()
    {
        Log::info('=== ACHIEVEMENTS METODU BAŞLADI ===');
        
        $user = auth()->user();
        Log::info('Kullanıcı bilgileri:', [
            'user_id' => $user->id,
            'username' => $user->username,
            'level' => $user->level,
            'xp' => $user->xp
        ]);
        
        $topics = Topic::all();
        Log::info('Konular yüklendi:', [
            'topic_count' => $topics->count(),
            'topics' => $topics->map(function($topic) {
                return [
                    'id' => $topic->id,
                    'name' => $topic->name
                ];
            })->toArray()
        ]);
        
        Log::info('=== ROZETLER YÜKLENIYOR ===');
        // Rozetleri getir - Eager loading ile performans iyileştirmesi
        $badges = Badge::with(['achievement', 'triggers', 'users' => function($query) use ($user) {
            $query->where('user_id', $user->id);
        }])
        ->get();
        
        Log::info('Rozetler yüklendi:', [
            'badge_count' => $badges->count(),
            'badges' => $badges->map(function($badge) {
                $trigger = $badge->triggers->first();
                return [
                    'id' => $badge->id,
                    'name' => $badge->name,
                    'topic_id' => $trigger ? $trigger->topic_id : null,
                    'topic_name' => $trigger && $trigger->topic ? $trigger->topic->name : null
                ];
            })->toArray()
        ]);
        
        Log::info('=== ROZETLER İŞLENİYOR ===');
        $badges = $badges->map(function ($badge) use ($user) {
            // Kullanıcının bu rozeti kazanıp kazanmadığını kontrol et
            $badge->is_earned = $badge->users->isNotEmpty();
            
            $trigger = $badge->triggers->first();
            Log::info("Rozet işleniyor: {$badge->name}", [
                'badge_id' => $badge->id,
                'topic_id' => $trigger ? $trigger->topic_id : null,
                'topic_name' => $trigger && $trigger->topic ? $trigger->topic->name : null,
                'is_earned' => $badge->is_earned,
                'has_triggers' => $badge->triggers->isNotEmpty()
            ]);
            
            // Rozet için ilerlemeyi hesapla
            if ($trigger) {
                Log::info("Rozet tetikleyicisi bulundu:", [
                    'badge_name' => $badge->name,
                    'trigger_type' => $trigger->trigger_type,
                    'required_score' => $trigger->required_score,
                    'required_count' => $trigger->required_count,
                    'topic_id' => $trigger->topic_id
                ]);
                
                switch ($trigger->trigger_type) {
                    case 'required_score':
                        $bestScore = QuizSession::where('user_id', $user->id)
                            ->when($trigger->topic_id, function($query) use ($trigger) {
                                return $query->where('topic_id', $trigger->topic_id);
                            })
                            ->max('score');
                        $badge->progress = round(min(100, ($bestScore / $trigger->required_score) * 100));
                        Log::info("Skor bazlı ilerleme hesaplandı:", [
                            'best_score' => $bestScore,
                            'required_score' => $trigger->required_score,
                            'progress' => $badge->progress,
                            'topic_id' => $trigger->topic_id
                        ]);
                        break;
                        
                    case 'required_count':
                        $completedCount = QuizSession::where('user_id', $user->id)
                            ->when($trigger->topic_id, function($query) use ($trigger) {
                                return $query->where('topic_id', $trigger->topic_id);
                            })
                            ->when($trigger->required_score, function($query) use ($trigger) {
                                return $query->where('score', '>=', $trigger->required_score);
                            })
                            ->count();
                        $badge->progress = round(min(100, ($completedCount / $trigger->required_count) * 100));
                        Log::info("Sayı bazlı ilerleme hesaplandı:", [
                            'completed_count' => $completedCount,
                            'required_count' => $trigger->required_count,
                            'progress' => $badge->progress,
                            'topic_id' => $trigger->topic_id
                        ]);
                        break;
                        
                    case 'streak':
                        $query = QuizSession::where('user_id', $user->id)
                            ->when($trigger->topic_id, function($query) use ($trigger) {
                                return $query->where('topic_id', $trigger->topic_id);
                            });
                            
                        if ($trigger->required_score) {
                            $query->where('score', '>=', $trigger->required_score);
                        }
                        
                        $currentStreak = $query->orderBy('created_at', 'desc')
                            ->limit($trigger->required_count)
                            ->count();
                            
                        $badge->progress = round(min(100, ($currentStreak / $trigger->required_count) * 100));
                        Log::info("Seri bazlı ilerleme hesaplandı:", [
                            'current_streak' => $currentStreak,
                            'required_count' => $trigger->required_count,
                            'required_score' => $trigger->required_score,
                            'progress' => $badge->progress,
                            'topic_id' => $trigger->topic_id
                        ]);
                        break;
                        
                    default:
                        $badge->progress = 0;
                        Log::warning("Bilinmeyen tetikleyici tipi:", [
                            'trigger_type' => $trigger->trigger_type
                        ]);
                }
            } else {
                $badge->progress = $badge->is_earned ? 100 : 0;
                Log::info("Tetikleyici olmayan rozet işlendi:", [
                    'badge_name' => $badge->name,
                    'progress' => $badge->progress
                ]);
            }
            
            return $badge;
        });
        
        Log::info('=== BAŞARIMLAR YÜKLENIYOR ===');
        // Başarımları getir - Eager loading ile performans iyileştirmesi
        $achievements = Achievement::with(['badges', 'badges.users' => function($query) use ($user) {
            $query->where('user_id', $user->id);
        }])->get();
        
        Log::info('Başarımlar yüklendi:', [
            'achievement_count' => $achievements->count(),
            'achievements' => $achievements->map(function($achievement) {
                return [
                    'id' => $achievement->id,
                    'name' => $achievement->name,
                    'badge_count' => $achievement->badges->count()
                ];
            })->toArray()
        ]);
        
        Log::info('=== BAŞARIMLAR İŞLENİYOR ===');
        $achievements = $achievements->map(function ($achievement) use ($user) {
            // Başarımın durumunu kontrol et
            $progressInfo = $achievement->checkProgress($user);
            
            $achievement->is_completed = $progressInfo['is_completed'];
            $achievement->progress = $progressInfo['progress'];
            
            Log::info("Başarım işlendi: {$achievement->name}", [
                'achievement_id' => $achievement->id,
                'requirement_type' => $achievement->requirement_type,
                'current_value' => $progressInfo['current_value'],
                'required_value' => $progressInfo['required_value'],
                'is_completed' => $achievement->is_completed,
                'progress' => $achievement->progress,
                'status' => $achievement->is_completed ? 'completed' : 'in-progress'
            ]);
            
            return $achievement;
        });
        
        Log::info('=== İSTATİSTİKLER HESAPLANIYOR ===');
        // İstatistikleri tek sorguda hesapla
        $stats = DB::table('badges')
            ->selectRaw('COUNT(*) as total_badges')
            ->selectRaw('(SELECT COUNT(*) FROM user_badges WHERE user_id = ?) as earned_badges', [$user->id])
            ->first();
            
        $completionRate = $stats->total_badges > 0 
            ? round(($stats->earned_badges / $stats->total_badges) * 100)
            : 0;
            
        Log::info('İstatistikler hesaplandı:', [
            'total_badges' => $stats->total_badges,
            'earned_badges' => $stats->earned_badges,
            'completion_rate' => $completionRate,
            'total_xp' => $user->xp
        ]);
        
        Log::info('=== VIEW RENDER EDİLİYOR ===');
        Log::info('View parametreleri:', [
            'badge_count' => $badges->count(),
            'achievement_count' => $achievements->count(),
            'topic_count' => $topics->count(),
            'total_badges' => $stats->total_badges,
            'earned_badges' => $stats->earned_badges,
            'total_xp' => $user->xp,
            'completion_rate' => $completionRate
        ]);
        
        return view('profile.profile-achievements', [
            'badges' => $badges,
            'achievements' => $achievements,
            'topics' => $topics,
            'totalBadges' => $stats->total_badges,
            'earnedBadges' => $stats->earned_badges,
            'totalXP' => $user->xp,
            'completionRate' => $completionRate
        ]);
    }

    public function history(Request $request)
    {
        $user = Auth::user();
        
        // Quiz sessions sorgusu
        $query = QuizSession::with(['topic', 'difficultyLevel'])
            ->where('user_id', $user->id);
        
        // Filtreleme
        if ($request->filled('topic')) {
            $query->where('topic_id', $request->topic);
        }
        
        if ($request->filled('difficulty')) {
            $query->where('difficulty_level_id', $request->difficulty);
        }
        
        if ($request->filled('date_range')) {
            switch ($request->date_range) {
                case 'today':
                    $query->whereDate('created_at', now());
                    break;
                case 'week':
                    $query->where('created_at', '>=', now()->subWeek());
                    break;
                case 'month':
                    $query->where('created_at', '>=', now()->subMonth());
                    break;
                case '3months':
                    $query->where('created_at', '>=', now()->subMonths(3));
                    break;
            }
        }
        
        // Sayfalama ile sıralama
        $quizSessions = $query->orderBy('created_at', 'desc')->paginate(10);
        
        // İstatistikler
        $allSessions = QuizSession::where('user_id', $user->id)->get();
        $totalQuizzes = $allSessions->count();
        $averageScore = $totalQuizzes > 0 ? $allSessions->avg('score') : 0;
        $averageAccuracy = $totalQuizzes > 0 ? 
            $allSessions->avg(function($session) {
                return ($session->correct_answers / $session->total_questions) * 100;
            }) : 0;
        $totalXP = $allSessions->sum('xp_earned');
        
        // Filtre seçenekleri
        $topics = \App\Models\Topic::all();
        $difficulties = \App\Models\DifficultyLevel::all();
        
        return view('profile.profile-history', compact(
            'quizSessions',
            'totalQuizzes',
            'averageScore',
            'averageAccuracy',
            'totalXP',
            'topics',
            'difficulties'
        ));
    }


}
