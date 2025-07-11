<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class GitHubWebhook extends Model
{
    protected $fillable = [
        'github_repository_id',
        'event_type',
        'action',
        'github_delivery_id',
        'payload',
        'status',
        'error_message',
        'processing_result',
        'processed_at',
    ];

    protected $casts = [
        'payload' => 'array',
        'processing_result' => 'array',
        'processed_at' => 'datetime',
    ];

    public function githubRepository(): BelongsTo
    {
        return $this->belongsTo(GitHubRepository::class);
    }

    public function markAsProcessed(array $result = []): void
    {
        $this->update([
            'status' => 'processed',
            'processing_result' => $result,
            'processed_at' => now(),
        ]);
    }

    public function markAsFailed(string $errorMessage): void
    {
        $this->update([
            'status' => 'failed',
            'error_message' => $errorMessage,
            'processed_at' => now(),
        ]);
    }

    public function markAsSkipped(string $reason = ''): void
    {
        $this->update([
            'status' => 'skipped',
            'error_message' => $reason,
            'processed_at' => now(),
        ]);
    }

    public function isPending(): bool
    {
        return $this->status === 'pending';
    }

    public function isProcessed(): bool
    {
        return $this->status === 'processed';
    }

    public function isFailed(): bool
    {
        return $this->status === 'failed';
    }

    public function isSkipped(): bool
    {
        return $this->status === 'skipped';
    }

    public function getIssueData(): ?array
    {
        return $this->payload['issue'] ?? null;
    }

    public function getPullRequestData(): ?array
    {
        return $this->payload['pull_request'] ?? null;
    }

    public function getCommitData(): array
    {
        return $this->payload['commits'] ?? [];
    }

    public function getRepositoryData(): ?array
    {
        return $this->payload['repository'] ?? null;
    }

    public function getSenderData(): ?array
    {
        return $this->payload['sender'] ?? null;
    }

    public function scopePending($query)
    {
        return $query->where('status', 'pending');
    }

    public function scopeProcessed($query)
    {
        return $query->where('status', 'processed');
    }

    public function scopeFailed($query)
    {
        return $query->where('status', 'failed');
    }

    public function scopeByEventType($query, string $eventType)
    {
        return $query->where('event_type', $eventType);
    }

    public function scopeByAction($query, string $action)
    {
        return $query->where('action', $action);
    }

    public static function createFromWebhook(
        GitHubRepository $repository,
        string $eventType,
        ?string $action,
        string $deliveryId,
        array $payload
    ): self {
        return static::create([
            'github_repository_id' => $repository->id,
            'event_type' => $eventType,
            'action' => $action,
            'github_delivery_id' => $deliveryId,
            'payload' => $payload,
        ]);
    }
}