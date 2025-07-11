<?php

namespace App\Services;

use App\Models\GitHubRepository;
use App\Models\Task;
use App\Models\TaskStatus;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

class GitHubSyncService
{
    private string $token;
    private string $baseUrl = 'https://api.github.com';

    public function __construct(string $token)
    {
        $this->token = $token;
    }

    public function importIssues(GitHubRepository $repository, array $selectedIssueIds, array $availableIssues): array
    {
        $imported = 0;
        $skipped = 0;
        $errors = [];

        foreach ($availableIssues as $issue) {
            if (!in_array($issue['id'], $selectedIssueIds)) {
                continue;
            }

            if ($issue['already_imported']) {
                $skipped++;
                continue;
            }

            try {
                $task = $repository->createTaskFromIssue($issue);
                $imported++;
                
                Log::info('Issue imported successfully', [
                    'issue_id' => $issue['id'],
                    'issue_number' => $issue['number'],
                    'task_id' => $task->id,
                    'repository' => $repository->full_name,
                ]);

            } catch (\Exception $e) {
                $skipped++;
                $errors[] = "Issue #{$issue['number']}: " . $e->getMessage();
                
                Log::error('Issue import failed', [
                    'issue_id' => $issue['id'],
                    'issue_number' => $issue['number'],
                    'error' => $e->getMessage(),
                ]);
            }
        }

        return [
            'imported_count' => $imported,
            'skipped_count' => $skipped,
            'errors' => $errors,
            'total_processed' => count($selectedIssueIds),
        ];
    }

    public function fullSync(GitHubRepository $repository): array
    {
        $imported = 0;
        $updated = 0;
        $skipped = 0;
        $errors = [];

        try {
            // Fetch all issues from GitHub
            $allIssues = $this->getAllIssues($repository->full_name);
            
            // Get existing tasks for this repository
            $existingTasks = Task::where('github_repository_id', $repository->id)
                ->whereNotNull('github_issue_id')
                ->get()
                ->keyBy('github_issue_id');

            foreach ($allIssues as $issue) {
                try {
                    $existingTask = $existingTasks->get($issue['id']);

                    if ($existingTask) {
                        // Update existing task
                        $this->updateTaskFromIssue($existingTask, $issue);
                        $updated++;
                    } else {
                        // Create new task
                        $repository->createTaskFromIssue($issue);
                        $imported++;
                    }

                } catch (\Exception $e) {
                    $skipped++;
                    $errors[] = "Issue #{$issue['number']}: " . $e->getMessage();
                    
                    Log::error('Issue sync failed', [
                        'issue_id' => $issue['id'],
                        'issue_number' => $issue['number'],
                        'error' => $e->getMessage(),
                    ]);
                }
            }

        } catch (\Exception $e) {
            Log::error('Full sync failed', [
                'repository' => $repository->full_name,
                'error' => $e->getMessage(),
            ]);
            throw $e;
        }

        return [
            'imported_count' => $imported,
            'updated_count' => $updated,
            'skipped_count' => $skipped,
            'errors' => $errors,
            'total_processed' => count($allIssues ?? []),
        ];
    }

    public function createIssueFromTask(GitHubRepository $repository, Task $task): array
    {
        try {
            $labels = [];
            
            // Add priority label
            if ($task->priority) {
                $labels[] = "priority: " . $task->priority;
            }

            // Add tags as labels
            if ($task->tags && $task->tags->isNotEmpty()) {
                foreach ($task->tags as $tag) {
                    $labels[] = $tag->name;
                }
            }

            $issueData = [
                'title' => $task->title,
                'body' => $task->description ?: 'Imported from kanban board',
                'labels' => $labels,
            ];

            // Assign to GitHub user if task is assigned and we can map the user
            if ($task->assignedUser && $task->assignedUser->github_username) {
                $issueData['assignees'] = [$task->assignedUser->github_username];
            }

            $response = Http::withToken($this->token)
                ->timeout(30)
                ->post($this->baseUrl . '/repos/' . $repository->full_name . '/issues', $issueData);

            if (!$response->successful()) {
                throw new \Exception('GitHub API request failed: ' . $response->body());
            }

            $issue = $response->json();
            
            Log::info('Task pushed to GitHub successfully', [
                'task_id' => $task->id,
                'issue_id' => $issue['id'],
                'issue_number' => $issue['number'],
                'repository' => $repository->full_name,
            ]);

            return $issue;

        } catch (\Exception $e) {
            Log::error('Task to GitHub push failed', [
                'task_id' => $task->id,
                'repository' => $repository->full_name,
                'error' => $e->getMessage(),
            ]);
            throw $e;
        }
    }

    public function updateIssueFromTask(GitHubRepository $repository, Task $task): array
    {
        if (!$task->github_issue_number) {
            throw new \Exception('Task is not linked to a GitHub issue');
        }

        try {
            $labels = [];
            
            // Add priority label
            if ($task->priority) {
                $labels[] = "priority: " . $task->priority;
            }

            // Add tags as labels
            if ($task->tags && $task->tags->isNotEmpty()) {
                foreach ($task->tags as $tag) {
                    $labels[] = $tag->name;
                }
            }

            $issueData = [
                'title' => $task->title,
                'body' => $task->description ?: 'Updated from kanban board',
                'labels' => $labels,
            ];

            // Update issue state based on task completion
            if ($task->completed_at) {
                $issueData['state'] = 'closed';
            } else {
                $issueData['state'] = 'open';
            }

            $response = Http::withToken($this->token)
                ->timeout(30)
                ->patch(
                    $this->baseUrl . '/repos/' . $repository->full_name . '/issues/' . $task->github_issue_number,
                    $issueData
                );

            if (!$response->successful()) {
                throw new \Exception('GitHub API request failed: ' . $response->body());
            }

            $issue = $response->json();
            
            Log::info('GitHub issue updated from task', [
                'task_id' => $task->id,
                'issue_number' => $task->github_issue_number,
                'repository' => $repository->full_name,
            ]);

            return $issue;

        } catch (\Exception $e) {
            Log::error('GitHub issue update failed', [
                'task_id' => $task->id,
                'issue_number' => $task->github_issue_number,
                'repository' => $repository->full_name,
                'error' => $e->getMessage(),
            ]);
            throw $e;
        }
    }

    private function getAllIssues(string $fullName): array
    {
        $allIssues = [];
        $page = 1;
        $perPage = 100;

        do {
            $response = Http::withToken($this->token)
                ->timeout(30)
                ->get($this->baseUrl . '/repos/' . $fullName . '/issues', [
                    'state' => 'all',
                    'per_page' => $perPage,
                    'page' => $page,
                ]);

            if (!$response->successful()) {
                throw new \Exception('GitHub API request failed: ' . $response->body());
            }

            $issues = $response->json();
            
            // Filter out pull requests
            $issues = array_filter($issues, function($issue) {
                return !isset($issue['pull_request']);
            });

            $allIssues = array_merge($allIssues, $issues);
            $page++;

        } while (count($issues) === $perPage);

        return $allIssues;
    }

    private function updateTaskFromIssue(Task $task, array $issueData): void
    {
        $updates = [
            'title' => $issueData['title'],
            'description' => $issueData['body'] ?? '',
            'github_data' => array_merge($task->github_data ?? [], [
                'html_url' => $issueData['html_url'],
                'state' => $issueData['state'],
                'labels' => $issueData['labels'] ?? [],
                'assignees' => $issueData['assignees'] ?? [],
                'updated_at' => $issueData['updated_at'],
            ]),
        ];

        // Update completion status based on issue state
        if ($issueData['state'] === 'closed' && !$task->completed_at) {
            $completedStatus = $this->getCompletedStatus($task->project);
            if ($completedStatus) {
                $updates['task_status_id'] = $completedStatus->id;
                $updates['completed_at'] = now();
            }
        } elseif ($issueData['state'] === 'open' && $task->completed_at) {
            $todoStatus = $this->getTodoStatus($task->project);
            if ($todoStatus) {
                $updates['task_status_id'] = $todoStatus->id;
                $updates['completed_at'] = null;
            }
        }

        // Update priority based on labels
        $priority = $this->extractPriorityFromLabels($issueData['labels'] ?? []);
        if ($priority) {
            $updates['priority'] = $priority;
        }

        $task->update($updates);

        Log::info('Task updated from GitHub issue', [
            'task_id' => $task->id,
            'issue_number' => $issueData['number'],
            'changes' => array_keys($updates),
        ]);
    }

    private function getCompletedStatus($project): ?TaskStatus
    {
        return $project->taskStatuses()
            ->where('slug', 'done')
            ->orWhere('name', 'Done')
            ->orWhere('name', 'Completed')
            ->first();
    }

    private function getTodoStatus($project): ?TaskStatus
    {
        return $project->taskStatuses()
            ->where('slug', 'todo')
            ->orWhere('name', 'To Do')
            ->orWhere('name', 'Todo')
            ->orderBy('sort_order')
            ->first();
    }

    private function extractPriorityFromLabels(array $labels): ?string
    {
        $labelNames = array_map(fn($label) => strtolower($label['name'] ?? ''), $labels);
        
        if (in_array('priority: high', $labelNames) || in_array('urgent', $labelNames)) {
            return 'high';
        }
        
        if (in_array('priority: medium', $labelNames) || in_array('enhancement', $labelNames)) {
            return 'medium';
        }
        
        if (in_array('priority: low', $labelNames)) {
            return 'low';
        }

        return null;
    }
}