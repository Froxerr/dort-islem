<?php

namespace App\Events;

use App\Models\User;
use App\Models\Friendship;
use Illuminate\Broadcasting\Channel;
use Illuminate\Broadcasting\InteractsWithSockets;
use Illuminate\Broadcasting\PresenceChannel;
use Illuminate\Broadcasting\PrivateChannel;
use Illuminate\Contracts\Broadcasting\ShouldBroadcastNow;
use Illuminate\Foundation\Events\Dispatchable;

class FriendRequestReceived implements ShouldBroadcastNow
{
    use Dispatchable, InteractsWithSockets;

    public $friendshipData;
    public $friendId;

    /**
     * Create a new event instance.
     */
    public function __construct(User $sender, Friendship $friendship)
    {
        $this->friendId = $friendship->friend_id;
        
        // Pre-extract all necessary data to avoid N+1 queries
        $this->friendshipData = [
            'id' => $friendship->id,
            'sender' => [
                'id' => $sender->id,
                'name' => $sender->name,
                'username' => $sender->username,
                'profile_image' => $sender->profile_image,
            ],
            'created_at' => $friendship->created_at,
            'type' => 'friend_request'
        ];
    }

    /**
     * Get the channels the event should broadcast on.
     *
     * @return array<int, \Illuminate\Broadcasting\Channel>
     */
    public function broadcastOn(): array
    {
        return [
            new PrivateChannel('user.' . $this->friendId),
        ];
    }

    /**
     * The event's broadcast name.
     */
    public function broadcastAs(): string
    {
        return 'friend.request';
    }

    /**
     * Get the data to broadcast.
     *
     * @return array<string, mixed>
     */
    public function broadcastWith(): array
    {
        return $this->friendshipData;
    }
} 