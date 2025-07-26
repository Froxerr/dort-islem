<?php

namespace App\Http\Controllers;

use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class LeaderboardController extends Controller
{
    public function index()
    {
        // En yüksek XP'li kullanıcılar
        $topUsers = User::select([
                'id', 'name', 'username', 'profile_image', 'level', 'xp',
                DB::raw('(SELECT COUNT(*) FROM quizsession WHERE user_id = users.id) as total_tests'),
                DB::raw('(SELECT COUNT(*) FROM user_badges WHERE user_id = users.id) as badge_count'),
                DB::raw('(SELECT AVG(score) FROM quizsession WHERE user_id = users.id) as avg_score')
            ])
            ->orderBy('xp', 'desc')
            ->orderBy('level', 'desc')
            ->limit(50)
            ->get()
            ->map(function($user, $index) {
                $user->rank = $index + 1;
                $user->avg_score = round($user->avg_score ?? 0);
                $user->total_tests = $user->total_tests ?? 0;
                $user->badge_count = $user->badge_count ?? 0;

                // Rank kategorisi
                if ($user->rank <= 3) {
                    $user->rank_category = 'podium';
                } elseif ($user->rank <= 10) {
                    $user->rank_category = 'top10';
                } elseif ($user->rank <= 25) {
                    $user->rank_category = 'top25';
                } else {
                    $user->rank_category = 'others';
                }

                return $user;
            });

        // İstatistikler
        $stats = [
            'total_users' => User::count(),
            'total_tests_completed' => DB::table('quizsession')->count(),
            'total_badges_earned' => DB::table('user_badges')->count(),
            'highest_level' => User::max('level') ?? 1,
            'average_level' => round(User::avg('level') ?? 1, 1)
        ];

        // Bu haftanın en aktif kullanıcıları
        $weeklyActive = User::select([
                'users.id', 'users.name', 'users.username', 'users.profile_image',
                'users.level', 'users.xp',
                DB::raw('COUNT(quizsession.id) as weekly_tests'),
                DB::raw('SUM(quizsession.score) as weekly_score')
            ])
            ->join('quizsession', 'users.id', '=', 'quizsession.user_id')
            ->where('quizsession.created_at', '>=', now()->subWeek())
            ->groupBy('users.id', 'users.name', 'users.username', 'users.profile_image', 'users.level', 'users.xp')
            ->orderBy('weekly_tests', 'desc')
            ->orderBy('weekly_score', 'desc')
            ->limit(10)
            ->get();

        return view('leaderboard.index', compact('topUsers', 'stats', 'weeklyActive'));
    }
}
