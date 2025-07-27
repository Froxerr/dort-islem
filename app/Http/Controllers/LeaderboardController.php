<?php

namespace App\Http\Controllers;

use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class LeaderboardController extends Controller
{
    public function index()
    {
        // En yüksek XP'li kullanıcılar - sadece istatistik paylaşımına izin verenler
        $topUsers = User::select([
                'id', 'name', 'username', 'profile_image', 'level', 'xp', 'show_stats', 'profile_visibility',
                DB::raw('(SELECT COUNT(*) FROM quizsession WHERE user_id = users.id) as total_tests'),
                DB::raw('(SELECT COUNT(*) FROM user_badges WHERE user_id = users.id) as badge_count'),
                DB::raw('(SELECT AVG(score) FROM quizsession WHERE user_id = users.id) as avg_score')
            ])
            ->where(function($query) {
                // Sadece istatistiklerini paylaşan ve profili public/friends olan kullanıcılar
                $query->where('show_stats', true)
                      ->whereIn('profile_visibility', ['public', 'friends']);
            })
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

        // İstatistikler - sadece istatistik paylaşan kullanıcılar dahil
        $stats = [
            'total_users' => User::where('show_stats', true)->whereIn('profile_visibility', ['public', 'friends'])->count(),
            'total_tests_completed' => DB::table('quizsession')
                ->join('users', 'quizsession.user_id', '=', 'users.id')
                ->where('users.show_stats', true)
                ->whereIn('users.profile_visibility', ['public', 'friends'])
                ->count(),
            'total_badges_earned' => DB::table('user_badges')
                ->join('users', 'user_badges.user_id', '=', 'users.id')
                ->where('users.show_stats', true)
                ->whereIn('users.profile_visibility', ['public', 'friends'])
                ->count(),
            'highest_level' => User::where('show_stats', true)->whereIn('profile_visibility', ['public', 'friends'])->max('level') ?? 1,
            'average_level' => round(User::where('show_stats', true)->whereIn('profile_visibility', ['public', 'friends'])->avg('level') ?? 1, 1)
        ];

        // Bu haftanın en aktif kullanıcıları - privacy kontrolü ile
        $weeklyActive = User::select([
                'users.id', 'users.name', 'users.username', 'users.profile_image',
                'users.level', 'users.xp',
                DB::raw('COUNT(quizsession.id) as weekly_tests'),
                DB::raw('SUM(quizsession.score) as weekly_score')
            ])
            ->join('quizsession', 'users.id', '=', 'quizsession.user_id')
            ->where('quizsession.created_at', '>=', now()->subWeek())
            ->where('users.show_stats', true)
            ->whereIn('users.profile_visibility', ['public', 'friends'])
            ->groupBy('users.id', 'users.name', 'users.username', 'users.profile_image', 'users.level', 'users.xp')
            ->orderBy('weekly_tests', 'desc')
            ->orderBy('weekly_score', 'desc')
            ->limit(10)
            ->get();

        return view('leaderboard.index', compact('topUsers', 'stats', 'weeklyActive'));
    }
}
