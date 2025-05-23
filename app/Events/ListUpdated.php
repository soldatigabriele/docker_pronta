<?php

namespace App\Events;

use App\Models\ReusableList;
use Illuminate\Broadcasting\Channel;
use Illuminate\Broadcasting\InteractsWithSockets;
use Illuminate\Broadcasting\PresenceChannel;
use Illuminate\Broadcasting\PrivateChannel;
use Illuminate\Contracts\Broadcasting\ShouldBroadcast;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

class ListUpdated implements ShouldBroadcast
{
    use Dispatchable, InteractsWithSockets, SerializesModels;

    public $reusableList;

    /**
     * Create a new event instance.
     */
    public function __construct(ReusableList $reusableList)
    {
        $this->reusableList = $reusableList;
    }

    /**
     * Get the channels the event should broadcast on.
     *
     * @return array<int, \Illuminate\Broadcasting\Channel>
     */
    public function broadcastOn(): array
    {
        $channels = [];

        // Broadcast to the list owner
        $channels[] = new PrivateChannel('user.' . $this->reusableList->user_id);

        // Broadcast to all users who have access to this list
        $this->reusableList->load('shares.sharedWith');
        foreach ($this->reusableList->shares as $share) {
            if ($share->is_accepted) {
                $channels[] = new PrivateChannel('user.' . $share->shared_with_user_id);
            }
        }

        return $channels;
    }

    public function broadcastWith(): array
    {
        return [
            'list' => $this->reusableList->load('user:id,name,email', 'shares.sharedWith:id,name,email'),
            'timestamp' => now()->toISOString(),
        ];
    }

    public function broadcastAs(): string
    {
        return 'list.updated';
    }
}
