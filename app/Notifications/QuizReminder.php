<?php

namespace App\Notifications;

use Illuminate\Bus\Queueable;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

class QuizReminder extends Notification
{
    use Queueable;

    protected $daysSinceLastQuiz;

    /**
     * Create a new notification instance.
     */
    public function __construct(int $daysSinceLastQuiz)
    {
        $this->daysSinceLastQuiz = $daysSinceLastQuiz;
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
        if ($notifiable->email_reminders ?? false) {
            $channels[] = 'mail';
        }
        
        return $channels;
    }

    /**
     * Get the mail representation of the notification.
     */
    public function toMail(object $notifiable): MailMessage
    {
        $message = $this->daysSinceLastQuiz > 7 
            ? "Uzun zamandır quiz çözmiyorsunuz! Son quiz'inizi {$this->daysSinceLastQuiz} gün önce çözmüştünüz."
            : "Quiz çözmeye devam etmeyi unutmayın! Son quiz'inizi {$this->daysSinceLastQuiz} gün önce çözmüştünüz.";

        return (new MailMessage)
                    ->subject('🎯 Quiz Çözme Zamanı!')
                    ->greeting('Merhaba ' . $notifiable->name . '!')
                    ->line($message)
                    ->line('Matematik becerilerinizi geliştirmek için hemen bir quiz çözmeye ne dersiniz?')
                    ->line('🎮 Yeni sorular sizi bekliyor!')
                    ->line('📈 Seviye atlayın ve XP kazanın!')
                    ->action('Quiz Çözmeye Başla', url('/'))
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
            'type' => 'quiz_reminder',
            'message' => "Quiz çözmeyi unutmayın! Son quiz'inizi {$this->daysSinceLastQuiz} gün önce çözmüştünüz.",
            'data' => [
                'days_since_last_quiz' => $this->daysSinceLastQuiz
            ]
        ];
    }
}
