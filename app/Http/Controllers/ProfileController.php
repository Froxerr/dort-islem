<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use App\Models\User;
use App\Models\QuizSession;
use App\Models\Badge;
use Illuminate\Support\Facades\Log;

class ProfileController extends Controller
{
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

            // Son aktiviteleri al (son 5 quiz)
            $recentActivities = QuizSession::with(['topic', 'difficultyLevel'])
                ->where('user_id', $user->id)
                ->orderBy('created_at', 'desc')
                ->limit(5)
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

            return view('profile', compact(
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
}
