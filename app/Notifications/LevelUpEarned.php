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
        return ['database'];
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