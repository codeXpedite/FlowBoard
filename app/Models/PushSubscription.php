<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class PushSubscription extends Model
{
    use HasFactory;

    protected $fillable = [
        'user_id',
        'endpoint',
        'p256dh_key',
        'auth_key',
        'is_active',
        'subscribed_at',
        'last_used_at',
        'metadata',
    ];

    protected $casts = [
        'is_active' => 'boolean',
        'subscribed_at' => 'datetime',
        'last_used_at' => 'datetime',
        'metadata' => 'array',
    ];

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function scopeActive($query)
    {
        return $query->where('is_active', true);
    }

    public function scopeForUser($query, User $user)
    {
        return $query->where('user_id', $user->id);
    }

    public function markAsUsed()
    {
        $this->update(['last_used_at' => now()]);
    }

    public function deactivate()
    {
        $this->update(['is_active' => false]);
    }

    public function activate()
    {
        $this->update(['is_active' => true]);
    }

    public function isExpired(): bool
    {
        // Consider a subscription expired if not used for 30 days
        return $this->last_used_at && $this->last_used_at->lt(now()->subDays(30));
    }

    public function getBrowserInfo(): string
    {
        $metadata = $this->metadata ?? [];
        return $metadata['browser'] ?? 'Unknown';
    }

    public function getDeviceInfo(): string
    {
        $metadata = $this->metadata ?? [];
        return $metadata['device'] ?? 'Unknown';
    }
}