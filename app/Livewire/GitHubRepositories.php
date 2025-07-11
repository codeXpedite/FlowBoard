<?php

namespace App\Livewire;

use App\Models\Project;
use App\Models\GitHubRepository;
use App\Services\GitHubApiService;
use Livewire\Component;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\Facades\Log;

class GitHubRepositories extends Component
{
    public Project $project;
    public $showAddForm = false;
    public $repositoryUrl = '';
    public $githubToken = '';
    public $selectedRepository = null;
    public $availableRepositories = [];
    public $loading = false;
    public $searchQuery = '';

    protected $rules = [
        'repositoryUrl' => 'required|string|regex:/^https:\/\/github\.com\/[a-zA-Z0-9_.-]+\/[a-zA-Z0-9_.-]+$/',
    ];

    public function mount($project)
    {
        $this->project = Project::where('id', $project)
            ->where('owner_id', auth()->id())
            ->firstOrFail();
    }

    public function openAddForm()
    {
        if (!Gate::allows('update', $this->project)) {
            session()->flash('error', 'Bu projeye repository ekleme yetkiniz bulunmamaktadır.');
            return;
        }

        $this->showAddForm = true;
        $this->resetForm();
    }

    public function closeAddForm()
    {
        $this->showAddForm = false;
        $this->resetForm();
    }

    public function searchRepositories()
    {
        if (!$this->githubToken) {
            session()->flash('error', 'GitHub token gereklidir.');
            return;
        }

        $this->loading = true;
        
        try {
            $githubService = new GitHubApiService($this->githubToken);
            $this->availableRepositories = $githubService->getUserRepositories($this->searchQuery);
            
            if (empty($this->availableRepositories)) {
                session()->flash('message', 'Repository bulunamadı.');
            }
        } catch (\Exception $e) {
            Log::error('GitHub API error', ['error' => $e->getMessage()]);
            session()->flash('error', 'GitHub API hatası: ' . $e->getMessage());
        }
        
        $this->loading = false;
    }

    public function selectRepository($repositoryData)
    {
        $this->selectedRepository = $repositoryData;
        $this->repositoryUrl = $repositoryData['html_url'];
    }

    public function addRepository()
    {
        $this->validate();

        if (!Gate::allows('update', $this->project)) {
            session()->flash('error', 'Bu projeye repository ekleme yetkiniz bulunmamaktadır.');
            return;
        }

        try {
            $this->loading = true;

            if ($this->selectedRepository) {
                // Use selected repository data
                $repoData = $this->selectedRepository;
            } else {
                // Parse URL and fetch repository data
                $repoPath = $this->parseGitHubUrl($this->repositoryUrl);
                if (!$repoPath) {
                    session()->flash('error', 'Geçersiz GitHub repository URL\'si.');
                    return;
                }

                if (!$this->githubToken) {
                    session()->flash('error', 'GitHub token gereklidir.');
                    return;
                }

                $githubService = new GitHubApiService($this->githubToken);
                $repoData = $githubService->getRepository($repoPath);
            }

            // Check if repository already exists
            $existingRepo = GitHubRepository::where('github_id', $repoData['id'])->first();
            if ($existingRepo) {
                session()->flash('error', 'Bu repository zaten başka bir projede kullanılıyor.');
                return;
            }

            // Create repository record
            $repository = GitHubRepository::create([
                'project_id' => $this->project->id,
                'repository_name' => $repoData['name'],
                'github_id' => $repoData['id'],
                'full_name' => $repoData['full_name'],
                'description' => $repoData['description'],
                'default_branch' => $repoData['default_branch'] ?? 'main',
                'private' => $repoData['private'],
                'clone_url' => $repoData['clone_url'],
                'html_url' => $repoData['html_url'],
                'webhook_events' => ['issues', 'push', 'pull_request', 'issue_comment'],
            ]);

            // Setup webhook if token is provided
            if ($this->githubToken) {
                try {
                    $webhookId = $githubService->createWebhook(
                        $repoData['full_name'],
                        $repository->webhook_url,
                        $repository->webhook_secret,
                        $repository->getEnabledEvents()
                    );
                    
                    $repository->update(['webhook_id' => $webhookId]);
                    session()->flash('message', 'Repository başarıyla eklendi ve webhook kuruldu.');
                } catch (\Exception $e) {
                    Log::error('Webhook creation failed', ['error' => $e->getMessage()]);
                    session()->flash('message', 'Repository eklendi ancak webhook kurulumunda hata oluştu: ' . $e->getMessage());
                }
            } else {
                session()->flash('message', 'Repository eklendi. Webhook kurulumu için GitHub token gereklidir.');
            }

            $this->closeAddForm();

        } catch (\Exception $e) {
            Log::error('Repository addition failed', ['error' => $e->getMessage()]);
            session()->flash('error', 'Repository eklenirken hata oluştu: ' . $e->getMessage());
        } finally {
            $this->loading = false;
        }
    }

    public function removeRepository($repositoryId)
    {
        if (!Gate::allows('update', $this->project)) {
            session()->flash('error', 'Repository silme yetkiniz bulunmamaktadır.');
            return;
        }

        $repository = GitHubRepository::where('id', $repositoryId)
            ->where('project_id', $this->project->id)
            ->first();

        if ($repository) {
            // TODO: Remove webhook from GitHub if needed
            $repository->delete();
            session()->flash('message', 'Repository başarıyla kaldırıldı.');
        }
    }

    public function toggleRepository($repositoryId)
    {
        if (!Gate::allows('update', $this->project)) {
            session()->flash('error', 'Repository durumu değiştirme yetkiniz bulunmamaktadır.');
            return;
        }

        $repository = GitHubRepository::where('id', $repositoryId)
            ->where('project_id', $this->project->id)
            ->first();

        if ($repository) {
            $repository->update(['active' => !$repository->active]);
            $status = $repository->active ? 'aktif' : 'pasif';
            session()->flash('message', "Repository {$status} duruma getirildi.");
        }
    }

    public function syncRepository($repositoryId)
    {
        if (!Gate::allows('update', $this->project)) {
            session()->flash('error', 'Repository senkronizasyon yetkiniz bulunmamaktadır.');
            return;
        }

        $repository = GitHubRepository::where('id', $repositoryId)
            ->where('project_id', $this->project->id)
            ->first();

        if ($repository) {
            $repository->updateLastSync();
            session()->flash('message', 'Repository son senkronizasyon zamanı güncellendi.');
        }
    }

    private function parseGitHubUrl(string $url): ?string
    {
        if (preg_match('/github\.com\/([^\/]+\/[^\/]+)/', $url, $matches)) {
            return $matches[1];
        }
        return null;
    }

    private function resetForm()
    {
        $this->repositoryUrl = '';
        $this->githubToken = '';
        $this->selectedRepository = null;
        $this->availableRepositories = [];
        $this->searchQuery = '';
        $this->resetValidation();
    }

    public function render()
    {
        $repositories = $this->project->githubRepositories()
            ->orderBy('active', 'desc')
            ->orderBy('created_at', 'desc')
            ->get();

        return view('livewire.git-hub-repositories', compact('repositories'))
            ->layout('layouts.app');
    }
}