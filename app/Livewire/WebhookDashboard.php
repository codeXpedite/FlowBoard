<?php

namespace App\Livewire;

use App\Models\Project;
use App\Models\GitHubWebhook;
use App\Models\GitHubRepository;
use App\Services\GitHubWebhookService;
use Livewire\Component;
use Livewire\WithPagination;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\Facades\Log;

class WebhookDashboard extends Component
{
    use WithPagination;

    public Project $project;
    public $selectedRepository = '';
    public $selectedStatus = '';
    public $selectedEventType = '';
    public $dateFrom = '';
    public $dateTo = '';
    public $showFilters = false;
    public $selectedWebhook = null;
    public $showWebhookDetails = false;

    protected $queryString = [
        'selectedRepository' => ['except' => ''],
        'selectedStatus' => ['except' => ''],
        'selectedEventType' => ['except' => ''],
        'dateFrom' => ['except' => ''],
        'dateTo' => ['except' => ''],
    ];

    public function mount($project)
    {
        $this->project = Project::where('id', $project)
            ->where('owner_id', auth()->id())
            ->firstOrFail();
    }

    public function toggleFilters()
    {
        $this->showFilters = !$this->showFilters;
    }

    public function clearFilters()
    {
        $this->selectedRepository = '';
        $this->selectedStatus = '';
        $this->selectedEventType = '';
        $this->dateFrom = '';
        $this->dateTo = '';
        $this->resetPage();
    }

    public function updatedSelectedRepository()
    {
        $this->resetPage();
    }

    public function updatedSelectedStatus()
    {
        $this->resetPage();
    }

    public function updatedSelectedEventType()
    {
        $this->resetPage();
    }

    public function updatedDateFrom()
    {
        $this->resetPage();
    }

    public function updatedDateTo()
    {
        $this->resetPage();
    }

    public function viewWebhookDetails($webhookId)
    {
        $this->selectedWebhook = GitHubWebhook::with('githubRepository')
            ->whereHas('githubRepository', function($query) {
                $query->where('project_id', $this->project->id);
            })
            ->findOrFail($webhookId);
        
        $this->showWebhookDetails = true;
    }

    public function closeWebhookDetails()
    {
        $this->selectedWebhook = null;
        $this->showWebhookDetails = false;
    }

    public function reprocessWebhook($webhookId)
    {
        if (!Gate::allows('update', $this->project)) {
            session()->flash('error', 'Webhook yeniden işleme yetkiniz bulunmamaktadır.');
            return;
        }

        try {
            $webhook = GitHubWebhook::with('githubRepository')
                ->whereHas('githubRepository', function($query) {
                    $query->where('project_id', $this->project->id);
                })
                ->findOrFail($webhookId);

            // Reset webhook status to pending
            $webhook->update([
                'status' => 'pending',
                'error_message' => null,
                'processing_result' => null,
                'processed_at' => null,
            ]);

            // Reprocess the webhook
            $webhookService = new GitHubWebhookService();
            $webhookService->processWebhook($webhook);

            session()->flash('message', 'Webhook başarıyla yeniden işlendi.');

            // Refresh the webhook details if it's currently shown
            if ($this->selectedWebhook && $this->selectedWebhook->id === $webhookId) {
                $this->selectedWebhook->refresh();
            }

        } catch (\Exception $e) {
            Log::error('Webhook reprocessing failed', [
                'webhook_id' => $webhookId,
                'error' => $e->getMessage(),
            ]);
            session()->flash('error', 'Webhook yeniden işlenirken hata oluştu: ' . $e->getMessage());
        }
    }

    public function deleteWebhook($webhookId)
    {
        if (!Gate::allows('update', $this->project)) {
            session()->flash('error', 'Webhook silme yetkiniz bulunmamaktadır.');
            return;
        }

        $webhook = GitHubWebhook::whereHas('githubRepository', function($query) {
            $query->where('project_id', $this->project->id);
        })->findOrFail($webhookId);

        $webhook->delete();
        session()->flash('message', 'Webhook kaydı silindi.');

        if ($this->selectedWebhook && $this->selectedWebhook->id === $webhookId) {
            $this->closeWebhookDetails();
        }
    }

    public function getWebhookStats()
    {
        $baseQuery = GitHubWebhook::whereHas('githubRepository', function($query) {
            $query->where('project_id', $this->project->id);
        });

        return [
            'total' => $baseQuery->count(),
            'pending' => $baseQuery->where('status', 'pending')->count(),
            'processed' => $baseQuery->where('status', 'processed')->count(),
            'failed' => $baseQuery->where('status', 'failed')->count(),
            'skipped' => $baseQuery->where('status', 'skipped')->count(),
            'today' => $baseQuery->whereDate('created_at', today())->count(),
            'this_week' => $baseQuery->whereBetween('created_at', [
                now()->startOfWeek(),
                now()->endOfWeek()
            ])->count(),
        ];
    }

    public function getEventTypeStats()
    {
        return GitHubWebhook::whereHas('githubRepository', function($query) {
            $query->where('project_id', $this->project->id);
        })
        ->selectRaw('event_type, COUNT(*) as count')
        ->groupBy('event_type')
        ->orderBy('count', 'desc')
        ->get()
        ->pluck('count', 'event_type')
        ->toArray();
    }

    public function render()
    {
        $webhooksQuery = GitHubWebhook::with(['githubRepository'])
            ->whereHas('githubRepository', function($query) {
                $query->where('project_id', $this->project->id);
            });

        // Apply filters
        if ($this->selectedRepository) {
            $webhooksQuery->where('github_repository_id', $this->selectedRepository);
        }

        if ($this->selectedStatus) {
            $webhooksQuery->where('status', $this->selectedStatus);
        }

        if ($this->selectedEventType) {
            $webhooksQuery->where('event_type', $this->selectedEventType);
        }

        if ($this->dateFrom) {
            $webhooksQuery->whereDate('created_at', '>=', $this->dateFrom);
        }

        if ($this->dateTo) {
            $webhooksQuery->whereDate('created_at', '<=', $this->dateTo);
        }

        $webhooks = $webhooksQuery->orderBy('created_at', 'desc')->paginate(20);

        $repositories = $this->project->githubRepositories()
            ->orderBy('full_name')
            ->get();

        $stats = $this->getWebhookStats();
        $eventTypeStats = $this->getEventTypeStats();

        $statusOptions = [
            'pending' => 'Beklemede',
            'processed' => 'İşlendi',
            'failed' => 'Başarısız',
            'skipped' => 'Atlandı',
        ];

        $eventTypes = GitHubWebhook::whereHas('githubRepository', function($query) {
            $query->where('project_id', $this->project->id);
        })
        ->distinct()
        ->pluck('event_type')
        ->sort()
        ->toArray();

        return view('livewire.webhook-dashboard', compact(
            'webhooks', 
            'repositories', 
            'stats', 
            'eventTypeStats', 
            'statusOptions', 
            'eventTypes'
        ))->layout('layouts.app');
    }
}