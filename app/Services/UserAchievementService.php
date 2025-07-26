<?php

namespace App\Services;

use App\Models\User;
use App\Models\Level;
use App\Models\QuizSession;
use App\Models\Achievement;
use App\Notifications\LevelUpEarned;
use App\Notifications\AchievementEarned;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class UserAchievementService
{
    public function processAchievements(User $user, QuizSession $quizSession)
    {
        Log::info('XP ve Level işlemi başladı', [
            'user_id' => $user->id,
            'quiz_session_id' => $quizSession->id
        ]);

        // Transaction başlat
        DB::beginTransaction();

        try {
            // XP'yi güncelle ve level kontrolü yap
            $this->updateXPAndCheckLevel($user, $quizSession->xp_earned);
            
            // Achievement kontrolü yap
            $this->checkAchievements($user, $quizSession);

            DB::commit();
            Log::info('XP ve Level işlemi başarılı');
            return true;
        } catch (\Exception $e) {
            DB::rollback();
            Log::error('XP ve Level işlemi hatası: ' . $e->getMessage());
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

        $oldLevel = $user->level;
        
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
            if ($newLevel->level != $oldLevel) {
                $user->level = $newLevel->level;
                Log::info('Level güncellendi', [
                    'user_id' => $user->id,
                    'new_level' => $user->level,
                    'xp' => $user->xp
                ]);

                // Bir sonraki level için gereken XP'yi bul
                $nextLevel = DB::table('levels')
                    ->where('xp_required_for_next_level', '>', $user->xp)
                    ->orderBy('xp_required_for_next_level', 'asc')
                    ->first();

                // Level up bildirimi gönder
                $user->notify(new LevelUpEarned(
                    $newLevel->level,
                    $earnedXP,
                    $nextLevel ? $nextLevel->xp_required_for_next_level : null
                ));

                Log::info('Level up bildirimi gönderildi', [
                    'user_id' => $user->id,
                    'new_level' => $newLevel->level,
                    'xp_earned' => $earnedXP,
                    'next_level_xp' => $nextLevel ? $nextLevel->xp_required_for_next_level : null
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

    private function checkAchievements(User $user, QuizSession $quizSession)
    {
        Log::info('Achievement kontrolü başladı', [
            'user_id' => $user->id,
            'quiz_session_id' => $quizSession->id
        ]);

        // Kullanıcının sahip olmadığı achievementları al
        $existingAchievementIds = $user->achievements()->pluck('achievements.id')->toArray();
        $availableAchievements = Achievement::whereNotIn('id', $existingAchievementIds)->get();

        foreach ($availableAchievements as $achievement) {
            $isEarned = false;

            // Achievement tipine göre kontrol et
            switch ($achievement->type) {
                case 'quiz_count':
                    $userQuizCount = QuizSession::where('user_id', $user->id)->count();
                    $isEarned = $userQuizCount >= $achievement->required_value;
                    break;

                case 'total_score':
                    $totalScore = QuizSession::where('user_id', $user->id)->sum('score');
                    $isEarned = $totalScore >= $achievement->required_value;
                    break;

                case 'accuracy_rate':
                    $accuracy = $user->getAverageAccuracy();
                    $isEarned = $accuracy >= $achievement->required_value;
                    break;

                case 'streak':
                    $streak = $user->getCurrentStreak();
                    $isEarned = $streak >= $achievement->required_value;
                    break;

                case 'level':
                    $isEarned = $user->level >= $achievement->required_value;
                    break;

                case 'xp_total':
                    $isEarned = $user->xp >= $achievement->required_value;
                    break;
            }

            if ($isEarned) {
                // Achievement'ı kullanıcıya ver
                $user->achievements()->attach($achievement->id, [
                    'earned_at' => now()
                ]);

                // XP ödülü varsa ver
                if ($achievement->xp_reward > 0) {
                    $user->xp += $achievement->xp_reward;
                    $user->save();
                }

                // Bildirim gönder
                $user->notify(new AchievementEarned($achievement, $achievement->xp_reward));

                Log::info('Achievement kazanıldı', [
                    'user_id' => $user->id,
                    'achievement_id' => $achievement->id,
                    'achievement_name' => $achievement->name,
                    'xp_reward' => $achievement->xp_reward
                ]);
            }
        }
    }
} 