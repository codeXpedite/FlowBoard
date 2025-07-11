<?php

namespace App\Livewire;

use App\Models\Project;
use Livewire\Component;
use Illuminate\Support\Facades\Gate;

class ProjectSettings extends Component
{
    public Project $project;
    public $name;
    public $description;
    public $settings = [];
    
    // Notification settings
    public $emailNotifications;
    public $taskAssignments;
    public $statusUpdates;
    public $comments;
    public $projectUpdates;

    public function mount($project)
    {
        $this->project = Project::where('id', $project)
            ->where('owner_id', auth()->id())
            ->firstOrFail();

        $this->name = $this->project->name;
        $this->description = $this->project->description;
        $this->settings = $this->project->settings ?? [];
        
        // Load notification settings
        $this->emailNotifications = $this->settings['email_notifications'] ?? true;
        $this->taskAssignments = $this->settings['task_assignments'] ?? true;
        $this->statusUpdates = $this->settings['status_updates'] ?? true;
        $this->comments = $this->settings['comments'] ?? true;
        $this->projectUpdates = $this->settings['project_updates'] ?? true;
    }

    public function updateProject()
    {
        // Check permissions
        if (!Gate::allows('update', $this->project)) {
            session()->flash('error', 'Bu projeyi güncelleme yetkiniz bulunmamaktadır.');
            return;
        }

        $this->validate([
            'name' => 'required|string|max:255',
            'description' => 'nullable|string|max:1000',
        ]);

        // Update notification settings
        $this->settings['email_notifications'] = $this->emailNotifications;
        $this->settings['task_assignments'] = $this->taskAssignments;
        $this->settings['status_updates'] = $this->statusUpdates;
        $this->settings['comments'] = $this->comments;
        $this->settings['project_updates'] = $this->projectUpdates;

        $this->project->update([
            'name' => $this->name,
            'description' => $this->description,
            'settings' => $this->settings,
        ]);

        session()->flash('message', 'Proje ayarları başarıyla güncellendi.');
    }

    public function deleteProject()
    {
        // Check permissions
        if (!Gate::allows('delete', $this->project)) {
            session()->flash('error', 'Bu projeyi silme yetkiniz bulunmamaktadır.');
            return;
        }

        $projectName = $this->project->name;
        $this->project->delete();

        session()->flash('message', "'{$projectName}' projesi başarıyla silindi.");
        
        return redirect()->route('dashboard');
    }

    public function archiveProject()
    {
        // Check permissions
        if (!Gate::allows('update', $this->project)) {
            session()->flash('error', 'Bu projeyi arşivleme yetkiniz bulunmamaktadır.');
            return;
        }

        $this->settings['archived'] = true;
        $this->project->update(['settings' => $this->settings]);

        session()->flash('message', 'Proje başarıyla arşivlendi.');
    }

    public function unarchiveProject()
    {
        // Check permissions
        if (!Gate::allows('update', $this->project)) {
            session()->flash('error', 'Bu projeyi arşivden çıkarma yetkiniz bulunmamaktadır.');
            return;
        }

        $this->settings['archived'] = false;
        $this->project->update(['settings' => $this->settings]);

        session()->flash('message', 'Proje arşivden başarıyla çıkarıldı.');
    }

    public function render()
    {
        return view('livewire.project-settings')
            ->layout('layouts.app');
    }
}