<?php

namespace App\Services;

use App\Models\GitHubWebhook;
use App\Models\Task;
use App\Models\TaskStatus;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;

class GitHubWebhookService
{
    public function processWebhook(GitHubWebhook $webhook): void
    {
        try {
            $result = match ($webhook->event_type) {
                'issues' => $this->handleIssueEvent($webhook),
                'push' => $this->handlePushEvent($webhook),
                'pull_request' => $this->handlePullRequestEvent($webhook),
                'issue_comment' => $this->handleIssueCommentEvent($webhook),
                default => $this->handleUnsupportedEvent($webhook),
            };

            $webhook->markAsProcessed($result);

        } catch (\Exception $e) {
            Log::error('GitHub webhook processing failed', [
                'webhook_id' => $webhook->id,
                'event_type' => $webhook->event_type,
                'error' => $e->getMessage(),
            ]);

            $webhook->markAsFailed($e->getMessage());
        }
    }

    private function handleIssueEvent(GitHubWebhook $webhook): array
    {
        $issueData = $webhook->getIssueData();
        $action = $webhook->action;
        $repository = $webhook->githubRepository;

        if (!$issueData) {
            return ['skipped' => true, 'reason' => 'No issue data in payload'];
        }

        switch ($action) {
            case 'opened':
                // Create new task from issue
                $task = $repository->createTaskFromIssue($issueData);
                
                Log::info('Task created from GitHub issue', [
                    'task_id' => $task->id,
                    'issue_number' => $issueData['number'],
                    'repository' => $repository->full_name,
                ]);

                return [
                    'action' => 'task_created',
                    'task_id' => $task->id,
                    'issue_number' => $issueData['number'],
                ];

            case 'closed':
                // Mark related task as completed
                $task = $this->findTaskByIssue($repository, $issueData['number']);
                if ($task) {
                    $completedStatus = $this->getCompletedStatus($task->project);
                    if ($completedStatus) {
                        $task->update([
                            'task_status_id' => $completedStatus->id,
                            'completed_at' => now(),
                        ]);

                        return [
                            'action' => 'task_completed',
                            'task_id' => $task->id,
                            'issue_number' => $issueData['number'],
                        ];
                    }
                }
                break;

            case 'reopened':
                // Reopen related task
                $task = $this->findTaskByIssue($repository, $issueData['number']);
                if ($task) {
                    $todoStatus = $this->getTodoStatus($task->project);
                    if ($todoStatus) {
                        $task->update([
                            'task_status_id' => $todoStatus->id,
                            'completed_at' => null,
                        ]);

                        return [
                            'action' => 'task_reopened',
                            'task_id' => $task->id,
                            'issue_number' => $issueData['number'],
                        ];
                    }
                }
                break;

            case 'edited':
                // Update task details
                $task = $this->findTaskByIssue($repository, $issueData['number']);
                if ($task) {
                    $task->update([
                        'title' => $issueData['title'],
                        'description' => $issueData['body'] ?? '',
                        'github_data' => array_merge($task->github_data ?? [], [
                            'html_url' => $issueData['html_url'],
                            'state' => $issueData['state'],
                            'updated_at' => $issueData['updated_at'],
                        ]),
                    ]);

                    return [
                        'action' => 'task_updated',
                        'task_id' => $task->id,
                        'issue_number' => $issueData['number'],
                    ];
                }
                break;
        }

        return ['skipped' => true, 'reason' => "Unsupported issue action: {$action}"];
    }

    private function handlePushEvent(GitHubWebhook $webhook): array
    {
        $commits = $webhook->getCommitData();
        $repository = $webhook->githubRepository;
        $completedTasks = [];

        foreach ($commits as $commit) {
            $message = $commit['message'] ?? '';
            $taskNumbers = $this->extractTaskReferencesFromCommit($message);

            foreach ($taskNumbers as $taskNumber) {
                $task = $this->findTaskByIssue($repository, $taskNumber);
                if ($task && !$task->completed_at) {
                    // Check if commit message indicates task completion
                    if ($this->commitIndicatesCompletion($message)) {
                        $completedStatus = $this->getCompletedStatus($task->project);
                        if ($completedStatus) {
                            $task->update([
                                'task_status_id' => $completedStatus->id,
                                'completed_at' => now(),
                            ]);

                            $completedTasks[] = [
                                'task_id' => $task->id,
                                'issue_number' => $taskNumber,
                                'commit_sha' => $commit['id'],
                            ];

                            Log::info('Task auto-completed from commit', [
                                'task_id' => $task->id,
                                'commit_sha' => $commit['id'],
                                'commit_message' => $message,
                            ]);
                        }
                    }
                }
            }
        }

        return [
            'action' => 'commits_processed',
            'completed_tasks' => $completedTasks,
            'total_commits' => count($commits),
        ];
    }

    private function handlePullRequestEvent(GitHubWebhook $webhook): array
    {
        $prData = $webhook->getPullRequestData();
        $action = $webhook->action;

        if (!$prData) {
            return ['skipped' => true, 'reason' => 'No pull request data in payload'];
        }

        // Extract task references from PR title and body
        $taskNumbers = array_merge(
            $this->extractTaskReferencesFromCommit($prData['title'] ?? ''),
            $this->extractTaskReferencesFromCommit($prData['body'] ?? '')
        );

        $updatedTasks = [];
        foreach ($taskNumbers as $taskNumber) {
            $task = $this->findTaskByIssue($webhook->githubRepository, $taskNumber);
            if ($task) {
                // Update task with PR information
                $githubData = $task->github_data ?? [];
                $githubData['pull_requests'] = $githubData['pull_requests'] ?? [];
                $githubData['pull_requests'][] = [
                    'number' => $prData['number'],
                    'title' => $prData['title'],
                    'html_url' => $prData['html_url'],
                    'state' => $prData['state'],
                    'action' => $action,
                ];

                $task->update(['github_data' => $githubData]);
                $updatedTasks[] = ['task_id' => $task->id, 'pr_number' => $prData['number']];
            }
        }

        return [
            'action' => 'pull_request_processed',
            'pr_number' => $prData['number'],
            'pr_action' => $action,
            'updated_tasks' => $updatedTasks,
        ];
    }

    private function handleIssueCommentEvent(GitHubWebhook $webhook): array
    {
        $issueData = $webhook->getIssueData();
        $commentData = $webhook->payload['comment'] ?? null;

        if (!$issueData || !$commentData) {
            return ['skipped' => true, 'reason' => 'Missing issue or comment data'];
        }

        $task = $this->findTaskByIssue($webhook->githubRepository, $issueData['number']);
        if ($task) {
            // Add comment to task (this would integrate with existing comment system)
            // For now, just log the comment
            Log::info('GitHub issue comment received', [
                'task_id' => $task->id,
                'issue_number' => $issueData['number'],
                'comment_author' => $commentData['user']['login'] ?? 'unknown',
            ]);

            return [
                'action' => 'comment_logged',
                'task_id' => $task->id,
                'issue_number' => $issueData['number'],
            ];
        }

        return ['skipped' => true, 'reason' => 'No matching task found'];
    }

    private function handleUnsupportedEvent(GitHubWebhook $webhook): array
    {
        $webhook->markAsSkipped("Unsupported event type: {$webhook->event_type}");
        return ['skipped' => true, 'reason' => "Unsupported event type: {$webhook->event_type}"];
    }

    private function findTaskByIssue(object $repository, int $issueNumber): ?Task
    {
        return Task::where('github_repository_id', $repository->id)
            ->where('github_issue_number', $issueNumber)
            ->first();
    }

    private function getCompletedStatus(object $project): ?TaskStatus
    {
        return $project->taskStatuses()
            ->where('slug', 'done')
            ->orWhere('name', 'Done')
            ->orWhere('name', 'Completed')
            ->first();
    }

    private function getTodoStatus(object $project): ?TaskStatus
    {
        return $project->taskStatuses()
            ->where('slug', 'todo')
            ->orWhere('name', 'To Do')
            ->orWhere('name', 'Todo')
            ->orderBy('sort_order')
            ->first();
    }

    private function extractTaskReferencesFromCommit(string $message): array
    {
        // Match patterns like: #123, fix #123, close #123, resolve #123
        preg_match_all('/(?:fix|close|resolve|ref|references?|see)\s*#(\d+)|#(\d+)/i', $message, $matches);
        
        $taskNumbers = array_filter(array_merge($matches[1], $matches[2]));
        return array_unique(array_map('intval', $taskNumbers));
    }

    private function commitIndicatesCompletion(string $message): bool
    {
        $completionKeywords = ['fix', 'close', 'resolve', 'complete', 'finish'];
        $lowerMessage = strtolower($message);
        
        foreach ($completionKeywords as $keyword) {
            if (str_contains($lowerMessage, $keyword)) {
                return true;
            }
        }
        
        return false;
    }
}