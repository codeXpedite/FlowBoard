<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Support\Str;

class GitHubRepository extends Model
{
    protected $fillable = [
        'project_id',
        'repository_name',
        'github_id',
        'full_name',
        'description',
        'default_branch',
        'private',
        'clone_url',
        'html_url',
        'webhook_secret',
        'webhook_id',
        'webhook_events',
        'active',
        'settings',
        'last_sync_at',
    ];

    protected $casts = [
        'private' => 'boolean',
        'active' => 'boolean',
        'webhook_events' => 'array',
        'settings' => 'array',
        'last_sync_at' => 'datetime',
    ];

    protected static function boot()
    {
        parent::boot();

        static::creating(function ($repository) {
            if (!$repository->webhook_secret) {
                $repository->webhook_secret = Str::random(40);
            }
        });
    }

    public function project(): BelongsTo
    {
        return $this->belongsTo(Project::class);
    }

    public function tasks(): HasMany
    {
        return $this->hasMany(Task::class, 'github_repository_id');
    }

    public function webhooks(): HasMany
    {
        return $this->hasMany(GitHubWebhook::class);
    }

    public function getOwnerAttribute(): string
    {
        return explode('/', $this->full_name)[0] ?? '';
    }

    public function getRepoNameAttribute(): string
    {
        return explode('/', $this->full_name)[1] ?? '';
    }

    public function getWebhookUrlAttribute(): string
    {
        return url("/api/webhooks/github/{$this->id}/{$this->webhook_secret}");
    }

    public function isWebhookActive(): bool
    {
        return !empty($this->webhook_id) && $this->active;
    }

    public function getEnabledEvents(): array
    {
        return $this->webhook_events ?? [
            'issues',
            'push',
            'pull_request',
            'issue_comment',
        ];
    }

    public function updateLastSync(): void
    {
        $this->update(['last_sync_at' => now()]);
    }

    public function scopeActive($query)
    {
        return $query->where('active', true);
    }

    public static function findByGitHubId(string $githubId): ?self
    {
        return static::where('github_id', $githubId)->first();
    }

    public function createTaskFromIssue(array $issueData): Task
    {
        // Find the first available status (typically "To Do")
        $firstStatus = $this->project->taskStatuses()->orderBy('sort_order')->first();
        
        return $this->project->tasks()->create([
            'title' => $issueData['title'],
            'description' => $issueData['body'] ?? '',
            'github_repository_id' => $this->id,
            'github_issue_number' => $issueData['number'],
            'github_issue_id' => $issueData['id'],
            'github_data' => [
                'html_url' => $issueData['html_url'],
                'state' => $issueData['state'],
                'labels' => $issueData['labels'] ?? [],
                'assignees' => $issueData['assignees'] ?? [],
                'created_at' => $issueData['created_at'],
                'updated_at' => $issueData['updated_at'],
            ],
            'task_status_id' => $firstStatus?->id,
            'created_by' => auth()->id() ?? $this->project->owner_id,
            'auto_created' => true,
            'priority' => $this->mapIssuePriorityFromLabels($issueData['labels'] ?? []),
        ]);
    }

    private function mapIssuePriorityFromLabels(array $labels): string
    {
        $labelNames = array_map(fn($label) => strtolower($label['name'] ?? ''), $labels);
        
        if (in_array('priority: high', $labelNames) || in_array('urgent', $labelNames)) {
            return 'high';
        }
        
        if (in_array('priority: medium', $labelNames) || in_array('enhancement', $labelNames)) {
            return 'medium';
        }
        
        return 'low';
    }
}