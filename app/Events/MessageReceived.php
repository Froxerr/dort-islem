<?php

namespace App\Events;

use App\Models\Message;
use Illuminate\Broadcasting\Channel;
use Illuminate\Broadcasting\InteractsWithSockets;
use Illuminate\Broadcasting\PresenceChannel;
use Illuminate\Broadcasting\PrivateChannel;
use Illuminate\Contracts\Broadcasting\ShouldBroadcastNow;
use Illuminate\Foundation\Events\Dispatchable;

class MessageReceived implements ShouldBroadcastNow
{
    use Dispatchable, InteractsWithSockets;

    public $messageData;
    public $userId;

    /**
     * Create a new event instance.
     */
    public function __construct(Message $message, $userId)
    {
        $this->userId = $userId;
        
        // Pre-extract all data to avoid N+1 queries during broadcasting
        // Load user relationship if not already loaded
        if (!$message->relationLoaded('user')) {
            $message->load('user');
        }
        
        $this->messageData = [
            'id' => $message->id,
            'conversation_id' => $message->conversation_id,
            'user_id' => $message->user_id,
            'body' => $message->body,
            'read_at' => $message->read_at,
            'created_at' => $message->created_at->toISOString(),
            'updated_at' => $message->updated_at->toISOString(),
            'user' => [
                'id' => $message->user->id,
                'name' => $message->user->name,
                'username' => $message->user->username,
                'profile_image' => $message->user->profile_image,
            ]
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
            new PrivateChannel('user.' . $this->userId),
        ];
    }

    /**
     * The event's broadcast name.
     */
    public function broadcastAs(): string
    {
        return 'message.received';
    }

    /**
     * Get the data to broadcast.
     */
    public function broadcastWith(): array
    {
        return [
            'message' => $this->messageData
        ];
    }
} 