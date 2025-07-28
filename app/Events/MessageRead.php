<?php

namespace App\Events;

use App\Models\Message;
use Illuminate\Broadcasting\Channel;
use Illuminate\Broadcasting\InteractsWithSockets;
use Illuminate\Broadcasting\PresenceChannel;
use Illuminate\Broadcasting\PrivateChannel;
use Illuminate\Contracts\Broadcasting\ShouldBroadcastNow;
use Illuminate\Foundation\Events\Dispatchable;

class MessageRead implements ShouldBroadcastNow
{
    use Dispatchable, InteractsWithSockets;

    public $messageId;
    public $conversationId;
    public $readAt;

    /**
     * Create a new event instance.
     */
    public function __construct(Message $message)
    {
        // Only store essential data needed for broadcasting
        $this->messageId = $message->id;
        $this->conversationId = $message->conversation_id;
        $this->readAt = $message->read_at;
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
        return 'message.read';
    }

    /**
     * Get the data to broadcast.
     *
     * @return array<string, mixed>
     */
    public function broadcastWith(): array
    {
        return [
            'message_id' => $this->messageId,
            'read_at' => $this->readAt,
            'conversation_id' => $this->conversationId,
        ];
    }
} 