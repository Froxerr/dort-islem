<?php

namespace App\Services;

use App\Models\User;
use App\Models\Level;
use App\Models\QuizSession;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class UserAchievementService
{
    public function processAchievements(User $user, QuizSession $quizSession)
    {
        Log::info('Achievement işlemi başladı', [
            'user_id' => $user->id,
            'quiz_session_id' => $quizSession->id
        ]);

        // Transaction başlat
        DB::beginTransaction();

        try {
            // XP'yi güncelle ve level kontrolü yap
            $this->updateXPAndCheckLevel($user, $quizSession->xp_earned);

            // Rozetleri kontrol et
            $this->checkAndAssignBadges($user);

            DB::commit();
            Log::info('Achievement işlemi başarılı');
            return true;
        } catch (\Exception $e) {
            DB::rollback();
            Log::error('Achievement işlemi hatası: ' . $e->getMessage());
            Log::error($e->getTraceAsString());
            return false;
        }
    }

    private function updateXPAndCheckLevel(User $user, int $earnedXP)
    {
        Log::info('XP güncelleniyor', [
            'user_id' => $user->id,
            'current_xp' => $user->xp,
            'earned_xp' => $earnedXP
        ]);

        // Kullanıcının XP'sini güncelle
        $user->xp += $earnedXP;
        
        // Kullanıcının yeni XP'sine göre olması gereken level'ı bul
        $newLevel = DB::table('levels')
            ->where('xp_required_for_next_level', '<=', $user->xp)
            ->orderBy('level', 'desc')
            ->first();

        if ($newLevel) {
            Log::info('Level kontrolü', [
                'user_id' => $user->id,
                'current_level' => $user->level,
                'new_level' => $newLevel->level,
                'current_xp' => $user->xp,
                'required_xp' => $newLevel->xp_required_for_next_level
            ]);

            // Eğer yeni level mevcut levelden büyükse güncelle
            if ($newLevel->level != $user->level) {
                $user->level = $newLevel->level;
                Log::info('Level güncellendi', [
                    'user_id' => $user->id,
                    'new_level' => $user->level,
                    'xp' => $user->xp
                ]);
            }
        }

        $user->save();

        // Bir sonraki level için gereken XP bilgisini log'a ekle
        $nextLevel = DB::table('levels')
            ->where('xp_required_for_next_level', '>', $user->xp)
            ->orderBy('xp_required_for_next_level', 'asc')
            ->first();

        if ($nextLevel) {
            Log::info('Sonraki level bilgisi', [
                'current_level' => $user->level,
                'next_level' => $nextLevel->level,
                'current_xp' => $user->xp,
                'required_xp_for_next' => $nextLevel->xp_required_for_next_level,
                'xp_needed' => $nextLevel->xp_required_for_next_level - $user->xp
            ]);
        }
    }

    private function checkAndAssignBadges(User $user)
    {
        // İlk test rozeti (badge_id: 1)
        $this->checkFirstQuizBadge($user);

        // 5000 XP rozeti (badge_id: 2)
        $this->checkXPMilestoneBadge($user);

        // 100 test rozeti (badge_id: 3)
        $this->checkQuizCountBadge($user);
    }

    private function checkFirstQuizBadge(User $user)
    {
        $hasFirstQuizBadge = DB::table('user_badges')
            ->where('user_id', $user->id)
            ->where('badge_id', 1)
            ->exists();

        if (!$hasFirstQuizBadge) {
            Log::info('İlk test rozeti veriliyor', ['user_id' => $user->id]);
            DB::table('user_badges')->insert([
                'user_id' => $user->id,
                'badge_id' => 1
            ]);
        }
    }

    private function checkXPMilestoneBadge(User $user)
    {
        if ($user->xp >= 5000) {
            $hasXPBadge = DB::table('user_badges')
                ->where('user_id', $user->id)
                ->where('badge_id', 2)
                ->exists();

            if (!$hasXPBadge) {
                Log::info('5000 XP rozeti veriliyor', ['user_id' => $user->id]);
                DB::table('user_badges')->insert([
                    'user_id' => $user->id,
                    'badge_id' => 2
                ]);
            }
        }
    }

    private function checkQuizCountBadge(User $user)
    {
        $quizCount = QuizSession::where('user_id', $user->id)->count();

        if ($quizCount >= 100) {
            $hasQuizCountBadge = DB::table('user_badges')
                ->where('user_id', $user->id)
                ->where('badge_id', 3)
                ->exists();

            if (!$hasQuizCountBadge) {
                Log::info('100 test rozeti veriliyor', ['user_id' => $user->id]);
                DB::table('user_badges')->insert([
                    'user_id' => $user->id,
                    'badge_id' => 3
                ]);
            }
        }
    }
} 