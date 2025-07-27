<?php

namespace App\Events;

use App\Models\User;
use App\Models\Friendship;
use Illuminate\Broadcasting\Channel;
use Illuminate\Broadcasting\InteractsWithSockets;
use Illuminate\Broadcasting\PresenceChannel;
use Illuminate\Broadcasting\PrivateChannel;
use Illuminate\Contracts\Broadcasting\ShouldBroadcast;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

class FriendRequestAccepted implements ShouldBroadcast
{
    use Dispatchable, InteractsWithSockets, SerializesModels;

    public $acceptedBy;
    public $sender;
    public $friendship;

    /**
     * Create a new event instance.
     */
    public function __construct(User $acceptedBy, User $sender, Friendship $friendship)
    {
        $this->acceptedBy = $acceptedBy;
        $this->sender = $sender;
        $this->friendship = $friendship;
    }

    /**
     * Get the channels the event should broadcast on.
     *
     * @return array<int, \Illuminate\Broadcasting\Channel>
     */
    public function broadcastOn(): array
    {
        return [
            new PrivateChannel('user.' . $this->sender->id),      // İsteği gönderen kişiye
            new PrivateChannel('user.' . $this->acceptedBy->id),  // Kabul eden kişiye
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
        return [
            'friendship_id' => $this->friendship->id,
            'accepted_by' => [
                'id' => $this->acceptedBy->id,
                'name' => $this->acceptedBy->name,
                'username' => $this->acceptedBy->username,
                'profile_image' => $this->acceptedBy->profile_image,
            ],
            'sender' => [
                'id' => $this->sender->id,
                'name' => $this->sender->name,
                'username' => $this->sender->username,
                'profile_image' => $this->sender->profile_image,
            ],
            'accepted_at' => $this->friendship->accepted_at,
            'type' => 'friend_accepted'
        ];
    }
} 