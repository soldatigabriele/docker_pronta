<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class ItemUsageStat extends Model
{
    protected $guarded = [];

    protected $casts = [
        'usage_count' => 'integer',
        'completion_count' => 'integer',
        'first_used_at' => 'datetime',
        'last_used_at' => 'datetime',
        'completion_rate' => 'decimal:2',
    ];

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public static function createOrUpdateStat(User $user, string $title): self
    {
        $hash = hash('sha256', strtolower(trim($title)));
        
        $stat = self::firstOrNew([
            'user_id' => $user->id,
            'item_title_hash' => $hash,
        ]);

        if ($stat->exists) {
            $stat->increment('usage_count');
            $stat->update([
                'last_used_at' => now(),
            ]);
        } else {
            $stat->fill([
                'item_title' => $title,
                'usage_count' => 1,
                'first_used_at' => now(),
                'last_used_at' => now(),
            ]);
            $stat->save();
        }

        return $stat;
    }

    public function incrementCompletion(): void
    {
        $this->increment('completion_count');
        $this->update([
            'completion_rate' => ($this->completion_count / $this->usage_count) * 100,
        ]);
    }
} 