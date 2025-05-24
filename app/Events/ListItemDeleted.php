<?php

namespace App\Events;

use App\Models\ListItem;
use App\Models\ReusableList;
use Illuminate\Broadcasting\Channel;
use Illuminate\Broadcasting\InteractsWithSockets;
use Illuminate\Broadcasting\PresenceChannel;
use Illuminate\Broadcasting\PrivateChannel;
use Illuminate\Contracts\Broadcasting\ShouldBroadcast;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Log;

class ListItemDeleted implements ShouldBroadcast
{
    use Dispatchable, InteractsWithSockets;
    
    // Don't use SerializesModels since we're dealing with a model that will be deleted

    public $itemData;
    public $listId;
    public $channelsData;

    /**
     * Create a new event instance.
     */
    public function __construct(ListItem $listItem)
    {
        // Store only the data we need, not the model itself
        $this->itemData = [
            'id' => $listItem->id,
            'title' => $listItem->title,
            'reusable_list_id' => $listItem->reusable_list_id,
        ];
        
        $this->listId = $listItem->reusable_list_id;
        
        // Store channel data for broadcasting
        $list = $listItem->reusableList;
        $this->channelsData = [];
        
        if ($list) {
            // Store list owner
            $this->channelsData[] = $list->user_id;
            
            // Store shared users
            if ($list->relationLoaded('shares') && $list->shares) {
                foreach ($list->shares as $share) {
                    if ($share->is_accepted && $share->shared_with_user_id) {
                        $this->channelsData[] = $share->shared_with_user_id;
                    }
                }
            }
        }
        
        // Remove duplicates
        $this->channelsData = array_unique($this->channelsData);
    }

    /**
     * Get the channels the event should broadcast on.
     *
     * @return array<int, \Illuminate\Broadcasting\Channel>
     */
    public function broadcastOn(): array
    {
        $channels = [];

        foreach ($this->channelsData as $userId) {
            $channels[] = new PrivateChannel('user.' . $userId);
        }

        Log::info('ListItemDeleted: Broadcasting to channels', [
            'item_id' => $this->itemData['id'],
            'list_id' => $this->listId,
            'channels_count' => count($channels),
            'user_ids' => $this->channelsData
        ]);

        return $channels;
    }

    public function broadcastWith(): array
    {
        return [
            'item' => $this->itemData,
            'list_id' => $this->listId,
            'timestamp' => now()->toISOString(),
        ];
    }

    public function broadcastAs(): string
    {
        return 'list.item.deleted';
    }
} 