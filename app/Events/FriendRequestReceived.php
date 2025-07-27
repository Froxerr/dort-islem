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

class FriendRequestReceived implements ShouldBroadcast
{
    use Dispatchable, InteractsWithSockets, SerializesModels;

    public $sender;
    public $friendship;

    /**
     * Create a new event instance.
     */
    public function __construct(User $sender, Friendship $friendship)
    {
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
            new PrivateChannel('user.' . $this->friendship->friend_id),
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
        return [
            'id' => $this->friendship->id,
            'sender' => [
                'id' => $this->sender->id,
                'name' => $this->sender->name,
                'username' => $this->sender->username,
                'profile_image' => $this->sender->profile_image,
            ],
            'created_at' => $this->friendship->created_at,
            'type' => 'friend_request'
        ];
    }
} 