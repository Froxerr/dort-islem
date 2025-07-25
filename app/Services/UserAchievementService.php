<?php

namespace App\Services;

use App\Models\User;
use App\Models\Level;
use App\Models\QuizSession;
use App\Notifications\LevelUpEarned;
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
} 