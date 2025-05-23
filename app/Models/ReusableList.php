<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class ReusableList extends Model
{
    protected $guarded = [];

    protected $casts = [
        'is_shared' => 'boolean',
        'is_public' => 'boolean',
        'sort_order' => 'integer',
    ];

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function items(): HasMany
    {
        return $this->hasMany(ListItem::class);
    }

    public function shares(): HasMany
    {
        return $this->hasMany(ListShare::class);
    }

    public function activeItems(): HasMany
    {
        return $this->hasMany(ListItem::class)->where('is_completed', false);
    }

    public function completedItems(): HasMany
    {
        return $this->hasMany(ListItem::class)->where('is_completed', true);
    }

    public function sharedWith(): HasMany
    {
        return $this->hasMany(ListShare::class)->where('is_accepted', true);
    }

    public function canUserAccess(User $user, string $permission = 'view'): bool
    {
        // Owner can always access
        if ($this->user_id === $user->id) {
            return true;
        }

        // Check if shared with user
        $share = $this->shares()
            ->where('shared_with_user_id', $user->id)
            ->where('is_accepted', true)
            ->first();

        if (!$share) {
            return false;
        }

        // Check if expired
        if ($share->isExpired()) {
            return false;
        }

        // Check permission level
        return match ($permission) {
            'view' => true,
            'edit' => $share->canEdit(),
            'admin' => $share->canAdmin(),
            default => false,
        };
    }
}
