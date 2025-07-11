<?php

namespace App\Livewire;

use App\Models\Project;
use App\Models\GitHubRepository;
use App\Models\Task;
use App\Services\GitHubApiService;
use App\Services\GitHubSyncService;
use Livewire\Component;
use Livewire\WithPagination;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\Facades\Log;

class GitHubSync extends Component
{
    use WithPagination;

    public Project $project;
    public $selectedRepository = '';
    public $githubToken = '';
    public $syncInProgress = false;
    public $lastSyncResult = null;
    public $showSyncHistory = false;
    public $issueState = 'open'; // open, closed, all
    public $selectedIssues = [];
    public $availableIssues = [];
    public $loading = false;
    public $showIssuePreview = false;

    protected $rules = [
        'githubToken' => 'required|string|min:40',
        'selectedRepository' => 'required|exists:github_repositories,id',
    ];

    public function mount($project)
    {
        $this->project = Project::where('id', $project)
            ->where('owner_id', auth()->id())
            ->firstOrFail();
    }

    public function loadIssues()
    {
        $this->validate([
            'githubToken' => 'required|string|min:40',
            'selectedRepository' => 'required|exists:github_repositories,id',
        ]);

        if (!Gate::allows('update', $this->project)) {
            session()->flash('error', 'GitHub issue yükleme yetkiniz bulunmamaktadır.');
            return;
        }

        try {
            $this->loading = true;
            $repository = GitHubRepository::findOrFail($this->selectedRepository);
            
            $githubService = new GitHubApiService($this->githubToken);
            $issues = $githubService->getIssues($repository->full_name, [
                'state' => $this->issueState,
                'per_page' => 100,
            ]);

            // Filter out pull requests (they appear as issues in GitHub API)
            $issues = array_filter($issues, function($issue) {
                return !isset($issue['pull_request']);
            });

            // Check which issues already exist as tasks
            $existingIssueIds = Task::where('github_repository_id', $repository->id)
                ->whereNotNull('github_issue_id')
                ->pluck('github_issue_id')
                ->toArray();

            $this->availableIssues = array_map(function($issue) use ($existingIssueIds) {
                $issue['already_imported'] = in_array($issue['id'], $existingIssueIds);
                return $issue;
            }, $issues);

            $this->showIssuePreview = true;
            session()->flash('message', count($this->availableIssues) . ' GitHub issue bulundu.');

        } catch (\Exception $e) {
            Log::error('GitHub issues loading failed', [
                'error' => $e->getMessage(),
                'repository_id' => $this->selectedRepository,
            ]);
            session()->flash('error', 'GitHub issue\'ları yüklenirken hata oluştu: ' . $e->getMessage());
        } finally {
            $this->loading = false;
        }
    }

    public function toggleIssueSelection($issueId)
    {
        if (in_array($issueId, $this->selectedIssues)) {
            $this->selectedIssues = array_diff($this->selectedIssues, [$issueId]);
        } else {
            $this->selectedIssues[] = $issueId;
        }
    }

    public function selectAllIssues()
    {
        $availableIssueIds = array_column(
            array_filter($this->availableIssues, function($issue) {
                return !$issue['already_imported'];
            }), 
            'id'
        );
        
        $this->selectedIssues = $availableIssueIds;
    }

    public function clearSelection()
    {
        $this->selectedIssues = [];
    }

    public function importSelectedIssues()
    {
        if (empty($this->selectedIssues)) {
            session()->flash('error', 'Lütfen import edilecek issue\'ları seçin.');
            return;
        }

        if (!Gate::allows('update', $this->project)) {
            session()->flash('error', 'Issue import etme yetkiniz bulunmamaktadır.');
            return;
        }

        try {
            $this->syncInProgress = true;
            $repository = GitHubRepository::findOrFail($this->selectedRepository);
            
            $syncService = new GitHubSyncService($this->githubToken);
            $result = $syncService->importIssues(
                $repository,
                $this->selectedIssues,
                $this->availableIssues
            );

            $this->lastSyncResult = $result;
            session()->flash('message', 
                $result['imported_count'] . ' issue başarıyla import edildi. ' .
                ($result['skipped_count'] > 0 ? $result['skipped_count'] . ' issue atlandı.' : '')
            );

            // Refresh issues list
            $this->loadIssues();
            $this->selectedIssues = [];

        } catch (\Exception $e) {
            Log::error('GitHub issues import failed', [
                'error' => $e->getMessage(),
                'repository_id' => $this->selectedRepository,
                'selected_issues' => $this->selectedIssues,
            ]);
            session()->flash('error', 'Issue import edilirken hata oluştu: ' . $e->getMessage());
        } finally {
            $this->syncInProgress = false;
        }
    }

    public function syncAllIssues()
    {
        $this->validate();

        if (!Gate::allows('update', $this->project)) {
            session()->flash('error', 'Tam senkronizasyon yetkiniz bulunmamaktadır.');
            return;
        }

        try {
            $this->syncInProgress = true;
            $repository = GitHubRepository::findOrFail($this->selectedRepository);
            
            $syncService = new GitHubSyncService($this->githubToken);
            $result = $syncService->fullSync($repository);

            $this->lastSyncResult = $result;
            session()->flash('message', 
                'Tam senkronizasyon tamamlandı. ' .
                $result['imported_count'] . ' yeni issue, ' .
                $result['updated_count'] . ' güncellenen issue.'
            );

            // Refresh the repository sync timestamp
            $repository->updateLastSync();

        } catch (\Exception $e) {
            Log::error('GitHub full sync failed', [
                'error' => $e->getMessage(),
                'repository_id' => $this->selectedRepository,
            ]);
            session()->flash('error', 'Tam senkronizasyon sırasında hata oluştu: ' . $e->getMessage());
        } finally {
            $this->syncInProgress = false;
        }
    }

    public function pushTaskToGitHub($taskId)
    {
        if (!$this->githubToken) {
            session()->flash('error', 'GitHub token gereklidir.');
            return;
        }

        if (!Gate::allows('update', $this->project)) {
            session()->flash('error', 'Task GitHub\'a gönderme yetkiniz bulunmamaktadır.');
            return;
        }

        try {
            $task = Task::where('id', $taskId)
                ->where('project_id', $this->project->id)
                ->firstOrFail();

            if ($task->github_repository_id) {
                session()->flash('error', 'Bu task zaten GitHub ile bağlı.');
                return;
            }

            if (!$this->selectedRepository) {
                session()->flash('error', 'Lütfen bir repository seçin.');
                return;
            }

            $repository = GitHubRepository::findOrFail($this->selectedRepository);
            $syncService = new GitHubSyncService($this->githubToken);
            $issueData = $syncService->createIssueFromTask($repository, $task);

            // Update task with GitHub information
            $task->update([
                'github_repository_id' => $repository->id,
                'github_issue_id' => $issueData['id'],
                'github_issue_number' => $issueData['number'],
                'github_data' => [
                    'html_url' => $issueData['html_url'],
                    'state' => $issueData['state'],
                    'created_at' => $issueData['created_at'],
                ],
            ]);

            session()->flash('message', "Task GitHub issue olarak oluşturuldu: #{$issueData['number']}");

        } catch (\Exception $e) {
            Log::error('Task to GitHub push failed', [
                'error' => $e->getMessage(),
                'task_id' => $taskId,
            ]);
            session()->flash('error', 'Task GitHub\'a gönderilirken hata oluştu: ' . $e->getMessage());
        }
    }

    public function getSyncStatistics()
    {
        if (!$this->selectedRepository) {
            return null;
        }

        $repository = GitHubRepository::find($this->selectedRepository);
        if (!$repository) {
            return null;
        }

        $totalTasks = $repository->tasks()->count();
        $githubLinkedTasks = $repository->tasks()->whereNotNull('github_issue_id')->count();
        $localOnlyTasks = $this->project->tasks()
            ->where(function($query) use ($repository) {
                $query->where('github_repository_id', '!=', $repository->id)
                      ->orWhereNull('github_repository_id');
            })
            ->count();

        return [
            'total_tasks' => $totalTasks,
            'github_linked' => $githubLinkedTasks,
            'local_only' => $localOnlyTasks,
            'last_sync' => $repository->last_sync_at?->diffForHumans(),
        ];
    }

    public function render()
    {
        $repositories = $this->project->githubRepositories()
            ->orderBy('full_name')
            ->get();

        $localTasks = collect();
        $syncStats = $this->getSyncStatistics();

        if ($this->selectedRepository) {
            $localTasks = $this->project->tasks()
                ->with(['taskStatus', 'assignedUser'])
                ->where(function($query) {
                    $repository = GitHubRepository::find($this->selectedRepository);
                    if ($repository) {
                        $query->where('github_repository_id', '!=', $repository->id)
                              ->orWhereNull('github_repository_id');
                    } else {
                        $query->whereNull('github_repository_id');
                    }
                })
                ->orderBy('created_at', 'desc')
                ->paginate(10);
        }

        return view('livewire.git-hub-sync', compact(
            'repositories',
            'localTasks', 
            'syncStats'
        ))->layout('layouts.app');
    }
}