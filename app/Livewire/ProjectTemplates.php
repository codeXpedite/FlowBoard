<?php

namespace App\Livewire;

use App\Models\ProjectTemplate;
use App\Services\ProjectTemplateService;
use Livewire\Component;
use Livewire\WithPagination;

class ProjectTemplates extends Component
{
    use WithPagination;

    public $search = '';
    public $selectedCategory = '';
    public $showCreateModal = false;
    public $showTemplateDetail = false;
    public $selectedTemplate = null;
    
    // Template creation form
    public $templateName = '';
    public $templateDescription = '';
    public $templateCategory = 'general';
    public $templateColor = '#3B82F6';
    public $isPublic = false;

    protected $queryString = [
        'search' => ['except' => ''],
        'selectedCategory' => ['except' => ''],
    ];

    public function updatingSearch()
    {
        $this->resetPage();
    }

    public function updatingSelectedCategory()
    {
        $this->resetPage();
    }

    public function clearFilters()
    {
        $this->search = '';
        $this->selectedCategory = '';
        $this->resetPage();
    }

    public function selectTemplate($templateId)
    {
        $this->selectedTemplate = ProjectTemplate::with('creator')->find($templateId);
        $this->showTemplateDetail = true;
    }

    public function closeTemplateDetail()
    {
        $this->showTemplateDetail = false;
        $this->selectedTemplate = null;
    }

    public function createProjectFromTemplate($templateId)
    {
        $template = ProjectTemplate::find($templateId);
        if ($template) {
            session(['selected_template' => $template->id]);
            return redirect()->route('dashboard');
        }
    }

    public function openCreateModal()
    {
        $this->showCreateModal = true;
        $this->reset(['templateName', 'templateDescription', 'templateCategory', 'templateColor', 'isPublic']);
    }

    public function closeCreateModal()
    {
        $this->showCreateModal = false;
    }

    public function createTemplate()
    {
        $this->validate([
            'templateName' => 'required|string|max:255',
            'templateDescription' => 'required|string|max:1000',
            'templateCategory' => 'required|string',
            'templateColor' => 'required|string',
        ]);

        $templateService = new ProjectTemplateService();
        
        $templateData = [
            'name' => $this->templateName,
            'description' => $this->templateDescription,
            'category' => $this->templateCategory,
            'color' => $this->templateColor,
            'task_statuses' => [
                ['name' => 'Yapılacak', 'color' => '#EF4444', 'order' => 0],
                ['name' => 'Devam Ediyor', 'color' => '#F59E0B', 'order' => 1],
                ['name' => 'Tamamlandı', 'color' => '#10B981', 'order' => 2],
            ],
            'tasks' => [],
            'settings' => [],
            'is_public' => $this->isPublic,
            'tags' => [],
        ];

        $templateService->createTemplate($templateData, auth()->user());
        
        session()->flash('message', 'Şablon başarıyla oluşturuldu.');
        $this->closeCreateModal();
    }

    public function getTemplatesProperty()
    {
        $templateService = new ProjectTemplateService();
        
        if ($this->search) {
            return $templateService->searchTemplates($this->search);
        }
        
        return $templateService->getTemplatesByCategory($this->selectedCategory ?: null);
    }

    public function getPopularTemplatesProperty()
    {
        $templateService = new ProjectTemplateService();
        return $templateService->getPopularTemplates(6);
    }

    public function getRecentTemplatesProperty()
    {
        $templateService = new ProjectTemplateService();
        return $templateService->getRecentTemplates(6);
    }

    public function getCategoriesProperty()
    {
        return ProjectTemplate::getCategories();
    }

    public function render()
    {
        return view('livewire.project-templates', [
            'templates' => $this->templates,
            'popularTemplates' => $this->popularTemplates,
            'recentTemplates' => $this->recentTemplates,
            'categories' => $this->categories,
        ])->layout('layouts.app');
    }
}