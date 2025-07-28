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

class FriendRequestAccepted implements ShouldBroadcastNow
{
    use Dispatchable, InteractsWithSockets;

    public $friendshipData;
    public $senderUserId;
    public $acceptedByUserId;

    /**
     * Create a new event instance.
     */
    public function __construct(User $acceptedBy, User $sender, Friendship $friendship)
    {
        $this->senderUserId = $sender->id;
        $this->acceptedByUserId = $acceptedBy->id;
        
        // Pre-extract all necessary data to avoid N+1 queries
        $this->friendshipData = [
            'friendship_id' => $friendship->id,
            'accepted_by' => [
                'id' => $acceptedBy->id,
                'name' => $acceptedBy->name,
                'username' => $acceptedBy->username,
                'profile_image' => $acceptedBy->profile_image,
            ],
            'sender' => [
                'id' => $sender->id,
                'name' => $sender->name,
                'username' => $sender->username,
                'profile_image' => $sender->profile_image,
            ],
            'accepted_at' => $friendship->accepted_at,
            'type' => 'friend_accepted'
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
            new PrivateChannel('user.' . $this->senderUserId),      // İsteği gönderen kişiye
            new PrivateChannel('user.' . $this->acceptedByUserId),  // Kabul eden kişiye
        ];
    }

    /**
     * The event's broadcast name.
     */
    public function broadcastAs(): string
    {
        return 'friend.accepted';
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