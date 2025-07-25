<?php

namespace App\Notifications;

use App\Models\Badge;
use Illuminate\Bus\Queueable;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

class BadgeEarned extends Notification
{
    use Queueable;

    protected $badge;
    protected $xpReward;
    protected $progress;

    /**
     * Create a new notification instance.
     */
    public function __construct(Badge $badge, int $xpReward = 0, array $progress = [])
    {
        $this->badge = $badge;
        $this->xpReward = $xpReward;
        $this->progress = $progress;
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
            'type' => 'badge_earned',
            'message' => "Tebrikler! {$this->badge->name} rozetini kazandın!",
            'data' => [
                'badge_id' => $this->badge->id,
                'badge_name' => $this->badge->name,
                'badge_description' => $this->badge->description,
                'badge_icon' => $this->badge->icon_filename,
                'xp_reward' => $this->xpReward,
                'progress' => $this->progress
            ]
        ];
    }
} 