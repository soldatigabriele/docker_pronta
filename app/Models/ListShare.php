<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class ListShare extends Model
{
    protected $guarded = [];

    protected $casts = [
        'is_accepted' => 'boolean',
        'can_share' => 'boolean',
        'invited_at' => 'datetime',
        'accepted_at' => 'datetime',
        'expires_at' => 'datetime',
    ];

    public function reusableList(): BelongsTo
    {
        return $this->belongsTo(ReusableList::class);
    }

    public function sharedBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'shared_by_user_id');
    }

    public function sharedWith(): BelongsTo
    {
        return $this->belongsTo(User::class, 'shared_with_user_id');
    }

    public function accept(): void
    {
        $this->update([
            'is_accepted' => true,
            'accepted_at' => now(),
        ]);
    }

    public function isExpired(): bool
    {
        return $this->expires_at && $this->expires_at->isPast();
    }

    public function canEdit(): bool
    {
        return in_array($this->permission_level, ['edit', 'admin']);
    }

    public function canAdmin(): bool
    {
        return $this->permission_level === 'admin';
    }
} 