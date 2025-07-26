<?php

namespace App\Notifications;

use Illuminate\Bus\Queueable;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

class WeeklySummary extends Notification
{
    use Queueable;

    protected $weeklyStats;

    /**
     * Create a new notification instance.
     */
    public function __construct(array $weeklyStats)
    {
        $this->weeklyStats = $weeklyStats;
    }

    /**
     * Get the notification's delivery channels.
     *
     * @return array<int, string>
     */
    public function via(object $notifiable): array
    {
        $channels = [];
        
        // E-posta bildirimi ayarı açıksa e-posta gönder
        if ($notifiable->email_weekly_summary ?? false) {
            $channels[] = 'mail';
        }
        
        return $channels;
    }

    /**
     * Get the mail representation of the notification.
     */
    public function toMail(object $notifiable): MailMessage
    {
        $stats = $this->weeklyStats;
        
        return (new MailMessage)
                    ->subject('📊 Haftalık Quiz Özeti')
                    ->greeting('Merhaba ' . $notifiable->name . '!')
                    ->line('Bu hafta quiz performansınızın özeti:')
                    ->line("🎯 **{$stats['total_quizzes']}** quiz çözdünüz")
                    ->line("✅ **{$stats['correct_answers']}** doğru cevap verdiniz")
                    ->line("📈 Ortalama doğruluk oranınız: **%{$stats['accuracy_rate']}**")
                    ->line("⭐ **{$stats['xp_earned']} XP** kazandınız")
                    ->line($stats['level_ups'] > 0 ? "🚀 **{$stats['level_ups']}** seviye atladınız!" : "")
                    ->line($stats['achievements'] > 0 ? "🏆 **{$stats['achievements']}** yeni başarım kazandınız!" : "")
                    ->action('Quiz Çözmeye Devam Et', url('/'))
                    ->salutation('Başarılarınızın devamını dileriz,' . "\n" . '🎯 ' . config('app.name') . ' Takımı');
    }

    /**
     * Get the array representation of the notification.
     *
     * @return array<string, mixed>
     */
    public function toArray(object $notifiable): array
    {
        return [
            'type' => 'weekly_summary',
            'message' => "Bu hafta {$this->weeklyStats['total_quizzes']} quiz çözdünüz!",
            'data' => $this->weeklyStats
        ];
    }
}
