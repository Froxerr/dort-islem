<?php

namespace App\Console\Commands;

use App\Models\User;
use App\Models\QuizSession;
use App\Notifications\WeeklySummary;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;
use Carbon\Carbon;

class SendWeeklySummary extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'quiz:weekly-summary';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Send weekly summary emails to users who have enabled this feature';

    /**
     * Execute the console command.
     */
    public function handle()
    {
        $this->info('Haftalık özet e-postaları gönderiliyor...');

        // Haftalık özet e-postası almak isteyen kullanıcıları al
        $users = User::where('email_weekly_summary', true)->get();

        if ($users->isEmpty()) {
            $this->info('Haftalık özet e-postası almak isteyen kullanıcı bulunamadı.');
            return;
        }

        $startOfWeek = Carbon::now()->startOfWeek();
        $endOfWeek = Carbon::now()->endOfWeek();

        $sentCount = 0;
        $errorCount = 0;

        foreach ($users as $user) {
            try {
                // Bu haftaki quiz istatistiklerini hesapla
                $weeklyStats = $this->calculateWeeklyStats($user, $startOfWeek, $endOfWeek);

                // Eğer bu hafta hiç quiz çözmemişse e-posta gönderme
                if ($weeklyStats['total_quizzes'] == 0) {
                    continue;
                }

                // Haftalık özet bildirimini gönder
                $user->notify(new WeeklySummary($weeklyStats));
                $sentCount++;

                $this->line("✅ {$user->name} ({$user->email}) - Özet gönderildi");

            } catch (\Exception $e) {
                $errorCount++;
                $this->error("❌ {$user->name} ({$user->email}) - Hata: " . $e->getMessage());
            }
        }

        $this->info("Haftalık özet gönderimi tamamlandı. Başarılı: {$sentCount}, Hatalı: {$errorCount}");
    }

    private function calculateWeeklyStats(User $user, Carbon $startOfWeek, Carbon $endOfWeek): array
    {
        // Bu haftaki quiz oturumlarını al
        $weeklyQuizzes = QuizSession::where('user_id', $user->id)
            ->whereBetween('created_at', [$startOfWeek, $endOfWeek])
            ->get();

        $totalQuizzes = $weeklyQuizzes->count();
        $correctAnswers = $weeklyQuizzes->sum('correct_answers');
        $totalQuestions = $weeklyQuizzes->sum(function($quiz) {
            return $quiz->correct_answers + $quiz->wrong_answers;
        });
        $xpEarned = $weeklyQuizzes->sum('xp_earned');

        // Doğruluk oranını hesapla
        $accuracyRate = $totalQuestions > 0 ? round(($correctAnswers / $totalQuestions) * 100, 1) : 0;

        // Bu haftaki seviye atlamaları (basit hesaplama)
        $levelUps = 0; // Bu daha karmaşık bir hesaplama gerektirir, şimdilik 0

        // Bu haftaki başarımlar (basit hesaplama)
        $achievements = 0; // Bu da daha karmaşık bir hesaplama gerektirir, şimdilik 0

        return [
            'total_quizzes' => $totalQuizzes,
            'correct_answers' => $correctAnswers,
            'accuracy_rate' => $accuracyRate,
            'xp_earned' => $xpEarned,
            'level_ups' => $levelUps,
            'achievements' => $achievements,
            'week_start' => $startOfWeek->format('d.m.Y'),
            'week_end' => $endOfWeek->format('d.m.Y')
        ];
    }
}
