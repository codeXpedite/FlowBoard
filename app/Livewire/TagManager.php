<?php

namespace App\Livewire;

use App\Models\Project;
use App\Models\Tag;
use Livewire\Component;
use Livewire\Attributes\Validate;

class TagManager extends Component
{
    public $project;
    public $showCreateForm = false;

    #[Validate('required|min:1|max:50')]
    public $name = '';
    
    #[Validate('required|regex:/^#[0-9A-Fa-f]{6}$/')]
    public $color = '#3B82F6';

    public function mount($projectId)
    {
        $this->project = Project::with('tags')->findOrFail($projectId);
    }

    public function createTag()
    {
        // Check permissions
        if (!auth()->user()->can('create tasks')) {
            session()->flash('error', 'Etiket oluşturma yetkiniz bulunmamaktadır.');
            return;
        }

        $this->validate();

        // Check if tag already exists in this project
        $existingTag = Tag::where('name', $this->name)
            ->where('project_id', $this->project->id)
            ->first();

        if ($existingTag) {
            $this->addError('name', 'Bu etiket zaten mevcut.');
            return;
        }

        Tag::create([
            'name' => $this->name,
            'color' => $this->color,
            'project_id' => $this->project->id,
        ]);

        $this->reset(['name', 'color']);
        $this->color = '#3B82F6';
        $this->showCreateForm = false;
        $this->project->load('tags');

        session()->flash('success', 'Etiket başarıyla oluşturuldu.');
    }

    public function deleteTag($tagId)
    {
        // Check permissions
        if (!auth()->user()->can('delete tasks')) {
            session()->flash('error', 'Etiket silme yetkiniz bulunmamaktadır.');
            return;
        }

        $tag = Tag::where('id', $tagId)
            ->where('project_id', $this->project->id)
            ->first();

        if ($tag) {
            $tag->delete();
            $this->project->load('tags');
            session()->flash('success', 'Etiket başarıyla silindi.');
        }
    }

    public function toggleCreateForm()
    {
        $this->showCreateForm = !$this->showCreateForm;
        if (!$this->showCreateForm) {
            $this->reset(['name', 'color']);
            $this->color = '#3B82F6';
        }
    }

    public function setColor($color)
    {
        $this->color = $color;
    }

    public function render()
    {
        return view('livewire.tag-manager', [
            'defaultColors' => Tag::getDefaultColors(),
        ]);
    }
}
