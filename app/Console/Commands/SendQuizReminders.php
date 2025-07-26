<?php

namespace App\Console\Commands;

use App\Models\User;
use App\Models\QuizSession;
use App\Notifications\QuizReminder;
use Illuminate\Console\Command;
use Carbon\Carbon;

class SendQuizReminders extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'quiz:reminders';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Send quiz reminder emails to inactive users';

    /**
     * Execute the console command.
     */
    public function handle()
    {
        $this->info('Quiz hatırlatma e-postaları gönderiliyor...');

        // Quiz hatırlatması almak isteyen kullanıcıları al
        $users = User::where('email_reminders', true)->get();

        if ($users->isEmpty()) {
            $this->info('Quiz hatırlatması almak isteyen kullanıcı bulunamadı.');
            return;
        }

        $sentCount = 0;
        $errorCount = 0;
        $skippedCount = 0;

        foreach ($users as $user) {
            try {
                // Kullanıcının son quiz tarihini al
                $lastQuiz = QuizSession::where('user_id', $user->id)
                    ->orderBy('created_at', 'desc')
                    ->first();

                if (!$lastQuiz) {
                    // Hiç quiz çözmemiş kullanıcılara da hatırlatma gönder
                    $daysSinceLastQuiz = 30; // Varsayılan değer
                } else {
                    $daysSinceLastQuiz = Carbon::now()->diffInDays($lastQuiz->created_at);
                }

                // Son 2 gün içinde quiz çözmüş kullanıcılara hatırlatma gönderme
                if ($daysSinceLastQuiz < 2) {
                    $skippedCount++;
                    continue;
                }

                // Quiz hatırlatma bildirimini gönder
                $user->notify(new QuizReminder($daysSinceLastQuiz));
                $sentCount++;

                $this->line("✅ {$user->name} ({$user->email}) - Hatırlatma gönderildi ({$daysSinceLastQuiz} gün)");

            } catch (\Exception $e) {
                $errorCount++;
                $this->error("❌ {$user->name} ({$user->email}) - Hata: " . $e->getMessage());
            }
        }

        $this->info("Quiz hatırlatma gönderimi tamamlandı. Başarılı: {$sentCount}, Hatalı: {$errorCount}, Atlandı: {$skippedCount}");
    }
}
