<?php

namespace App\Events;

use App\Models\ListItem;
use Illuminate\Broadcasting\Channel;
use Illuminate\Broadcasting\InteractsWithSockets;
use Illuminate\Broadcasting\PresenceChannel;
use Illuminate\Broadcasting\PrivateChannel;
use Illuminate\Contracts\Broadcasting\ShouldBroadcast;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

class ListItemCreated implements ShouldBroadcast
{
    use Dispatchable, InteractsWithSockets, SerializesModels;

    public $listItem;

    /**
     * Create a new event instance.
     */
    public function __construct(ListItem $listItem)
    {
        $this->listItem = $listItem;
    }

    /**
     * Get the channels the event should broadcast on.
     *
     * @return array<int, \Illuminate\Broadcasting\Channel>
     */
    public function broadcastOn(): array
    {
        $channels = [];
        $list = $this->listItem->reusableList;

        // Broadcast to the list owner
        $channels[] = new PrivateChannel('user.' . $list->user_id);

        // Broadcast to all users who have access to this list
        $list->load('shares.sharedWith');
        foreach ($list->shares as $share) {
            if ($share->is_accepted) {
                $channels[] = new PrivateChannel('user.' . $share->shared_with_user_id);
            }
        }

        return $channels;
    }

    public function broadcastWith(): array
    {
        return [
            'item' => $this->listItem->load('createdBy:id,name,email', 'completedBy:id,name,email'),
            'list_id' => $this->listItem->reusable_list_id,
            'timestamp' => now()->toISOString(),
        ];
    }

    public function broadcastAs(): string
    {
        return 'list.item.created';
    }
} 