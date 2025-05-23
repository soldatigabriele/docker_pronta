<?php

namespace App\Events;

use App\Models\ListShare;
use Illuminate\Broadcasting\Channel;
use Illuminate\Broadcasting\InteractsWithSockets;
use Illuminate\Broadcasting\PresenceChannel;
use Illuminate\Broadcasting\PrivateChannel;
use Illuminate\Contracts\Broadcasting\ShouldBroadcast;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

class ListShared implements ShouldBroadcast
{
    use Dispatchable, InteractsWithSockets, SerializesModels;

    public $listShare;

    /**
     * Create a new event instance.
     */
    public function __construct(ListShare $listShare)
    {
        $this->listShare = $listShare;
    }

    /**
     * Get the channels the event should broadcast on.
     *
     * @return array<int, \Illuminate\Broadcasting\Channel>
     */
    public function broadcastOn(): array
    {
        return [
            new PrivateChannel('user.' . $this->listShare->shared_with_user_id),
        ];
    }

    public function broadcastWith(): array
    {
        return [
            'share' => $this->listShare->load('reusableList', 'sharedBy:id,name,email'),
            'timestamp' => now()->toISOString(),
        ];
    }

    public function broadcastAs(): string
    {
        return 'list.shared';
    }
}
