<?php

namespace App\Livewire;

use App\Services\KeyboardShortcutService;
use Livewire\Component;
use Livewire\Attributes\On;

class ShortcutsHelp extends Component
{
    public bool $showModal = false;

    protected KeyboardShortcutService $shortcutService;

    public function boot(KeyboardShortcutService $shortcutService)
    {
        $this->shortcutService = $shortcutService;
    }

    #[On('show-shortcuts-modal')]
    public function showModal()
    {
        $this->showModal = true;
    }

    public function closeModal()
    {
        $this->showModal = false;
    }

    public function render()
    {
        return view('livewire.shortcuts-help', [
            'shortcuts' => $this->shortcutService->getShortcuts(),
            'isMac' => $this->shortcutService->isMac(),
        ]);
    }
}
