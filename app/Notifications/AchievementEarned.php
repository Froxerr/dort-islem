<?php

namespace App\Notifications;

use App\Models\Achievement;
use Illuminate\Bus\Queueable;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

class AchievementEarned extends Notification
{
    use Queueable;

    protected $achievement;
    protected $xpEarned;

    /**
     * Create a new notification instance.
     */
    public function __construct(Achievement $achievement, int $xpEarned = 0)
    {
        $this->achievement = $achievement;
        $this->xpEarned = $xpEarned;
    }

    /**
     * Get the notification's delivery channels.
     *
     * @return array<int, string>
     */
    public function via(object $notifiable): array
    {
        $channels = ['database'];
        
        // E-posta bildirimi ayarı açıksa e-posta da gönder
        if ($notifiable->email_achievements ?? true) {
            $channels[] = 'mail';
        }
        
        return $channels;
    }

    /**
     * Get the mail representation of the notification.
     */
    public function toMail(object $notifiable): MailMessage
    {
        return (new MailMessage)
                    ->subject('🏆 Yeni Başarım Kazandınız!')
                    ->greeting('Tebrikler ' . $notifiable->name . '!')
                    ->line("**{$this->achievement->name}** başarımını kazandınız! 🎉")
                    ->line($this->achievement->description)
                    ->line($this->xpEarned > 0 ? "Bu başarım için **{$this->xpEarned} XP** kazandınız." : "")
                    ->action('Başarımlarınızı Görün', url('/profile/achievements'))
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
            'type' => 'achievement',
            'message' => "Tebrikler! '{$this->achievement->name}' başarımını kazandınız!",
            'data' => [
                'achievement_id' => $this->achievement->id,
                'achievement_name' => $this->achievement->name,
                'achievement_description' => $this->achievement->description,
                'xp_earned' => $this->xpEarned
            ]
        ];
    }
}
