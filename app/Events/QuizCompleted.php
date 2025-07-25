<?php

namespace App\Events;

use App\Models\User;
use Illuminate\Broadcasting\Channel;
use Illuminate\Broadcasting\InteractsWithSockets;
use Illuminate\Broadcasting\PresenceChannel;
use Illuminate\Broadcasting\PrivateChannel;
use Illuminate\Contracts\Broadcasting\ShouldBroadcast;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

class QuizCompleted
{
    use Dispatchable, InteractsWithSockets, SerializesModels;

    /**
     * @var User
     */
    public $user;

    /**
     * @var int
     */
    public $totalscore;

    /**
     * @var int
     */
    public $correctAnswerCount;
    
    /**
     * @var array
     */
    public $topicStats;

    public function __construct(User $user, int $totalscore, int $correctAnswerCount, array $topicStats)
    {
        $this->user = $user;
        $this->totalscore = $totalscore;
        $this->correctAnswerCount = $correctAnswerCount;
        $this->topicStats = $topicStats;
    }

    /**
     * Get the channels the event should broadcast on.
     *
     * @return array<int, \Illuminate\Broadcasting\Channel>
     */
    public function broadcastOn(): array
    {
        return [
            new PrivateChannel('channel-name'),
        ];
    }
}
