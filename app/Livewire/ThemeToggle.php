<?php

namespace App\Livewire;

use App\Services\ThemeService;
use Livewire\Component;

class ThemeToggle extends Component
{
    public string $currentTheme;

    protected ThemeService $themeService;

    public function boot(ThemeService $themeService)
    {
        $this->themeService = $themeService;
    }

    public function mount()
    {
        $this->currentTheme = $this->themeService->getCurrentTheme();
    }

    public function toggleTheme()
    {
        $this->currentTheme = $this->themeService->toggleTheme();
        
        // Dispatch browser event to update body classes
        $this->dispatch('theme-changed', theme: $this->currentTheme);
        
        session()->flash('success', 'Tema başarıyla değiştirildi.');
    }

    public function setTheme(string $theme)
    {
        if (in_array($theme, ['light', 'dark'])) {
            $this->themeService->setTheme($theme);
            $this->currentTheme = $theme;
            $this->dispatch('theme-changed', theme: $this->currentTheme);
        }
    }

    public function render()
    {
        return view('livewire.theme-toggle', [
            'availableThemes' => $this->themeService->getAvailableThemes(),
            'isDarkTheme' => $this->themeService->isDarkTheme(),
        ]);
    }
}
