<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class ListItem extends Model
{
    protected $guarded = [];

    protected $casts = [
        'is_completed' => 'boolean',
        'completed_at' => 'datetime',
        'last_used_at' => 'datetime',
        'tags' => 'array',
        'usage_count' => 'integer',
        'sort_order' => 'integer',
    ];

    public function reusableList(): BelongsTo
    {
        return $this->belongsTo(ReusableList::class);
    }

    public function createdBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by_user_id');
    }

    public function completedBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'completed_by_user_id');
    }

    public function markCompleted(User $user): void
    {
        $this->update([
            'is_completed' => true,
            'completed_at' => now(),
            'completed_by_user_id' => $user->id,
            'usage_count' => $this->usage_count + 1,
            'last_used_at' => now(),
        ]);
    }

    public function markIncomplete(): void
    {
        $this->update([
            'is_completed' => false,
            'completed_at' => null,
            'completed_by_user_id' => null,
        ]);
    }
} 