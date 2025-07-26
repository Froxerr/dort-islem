<?php

namespace App\Notifications;

use Illuminate\Bus\Queueable;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

class LevelUpEarned extends Notification
{
    use Queueable;

    protected $newLevel;
    protected $xpEarned;
    protected $nextLevelXP;

    /**
     * Create a new notification instance.
     */
    public function __construct(int $newLevel, int $xpEarned, int $nextLevelXP)
    {
        $this->newLevel = $newLevel;
        $this->xpEarned = $xpEarned;
        $this->nextLevelXP = $nextLevelXP;
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
        if ($notifiable->email_level_up ?? true) {
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
                    ->subject('🎉 Seviye Atladınız!')
                    ->greeting('Tebrikler ' . $notifiable->name . '!')
                    ->line("**Seviye {$this->newLevel}**'e ulaştınız! ")
                    ->line("Bu quiz oturumunda **{$this->xpEarned} XP** kazandınız.")
                    ->line($this->nextLevelXP ? "Sonraki seviye için **" . ($this->nextLevelXP - ($notifiable->xp ?? 0)) . " XP** daha gerekiyor." : "Maksimum seviyeye ulaştınız!")
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
            'type' => 'level_up',
            'message' => "Tebrikler! Seviye {$this->newLevel}'e ulaştınız!",
            'data' => [
                'new_level' => $this->newLevel,
                'xp_earned' => $this->xpEarned,
                'next_level_xp' => $this->nextLevelXP
            ]
        ];
    }
} 