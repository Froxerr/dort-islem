<?php

namespace App\Events;

use App\Models\User;
use Illuminate\Broadcasting\Channel;
use Illuminate\Broadcasting\InteractsWithSockets;
use Illuminate\Broadcasting\PresenceChannel;
use Illuminate\Broadcasting\PrivateChannel;
use Illuminate\Contracts\Broadcasting\ShouldBroadcastNow;
use Illuminate\Foundation\Events\Dispatchable;

class UserTyping implements ShouldBroadcastNow
{
    use Dispatchable, InteractsWithSockets;

    public $userId;
    public $userName;
    public $userUsername;
    public $conversationId;
    public $isTyping;

    /**
     * Create a new event instance.
     */
    public function __construct(User $user, $conversationId, $isTyping = true)
    {
        // Only store minimal data needed for broadcasting
        $this->userId = $user->id;
        $this->userName = $user->name;
        $this->userUsername = $user->username;
        $this->conversationId = $conversationId;
        $this->isTyping = $isTyping;
    }

    /**
     * Get the channels the event should broadcast on.
     *
     * @return array<int, \Illuminate\Broadcasting\Channel>
     */
    public function broadcastOn(): array
    {
        return [
            new PrivateChannel('conversation.' . $this->conversationId),
        ];
    }

    /**
     * The event's broadcast name.
     */
    public function broadcastAs(): string
    {
        return 'user.typing';
    }

    /**
     * Get the data to broadcast.
     */
    public function broadcastWith(): array
    {
        return [
            'user' => [
                'id' => $this->userId,
                'name' => $this->userName,
                'username' => $this->userUsername,
            ],
            'conversation_id' => $this->conversationId,
            'is_typing' => $this->isTyping,
        ];
    }
} 