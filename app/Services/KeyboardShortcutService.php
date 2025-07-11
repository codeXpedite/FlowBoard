<?php

namespace App\Services;

class KeyboardShortcutService
{
    /**
     * Get all available keyboard shortcuts
     */
    public function getShortcuts(): array
    {
        return [
            'global' => [
                'title' => 'Global Shortcuts',
                'shortcuts' => [
                    ['key' => 'Ctrl + K', 'mac' => 'Cmd + K', 'description' => 'Quick search', 'action' => 'global-search'],
                    ['key' => 'Ctrl + N', 'mac' => 'Cmd + N', 'description' => 'New task', 'action' => 'new-task'],
                    ['key' => 'Ctrl + Shift + N', 'mac' => 'Cmd + Shift + N', 'description' => 'New project', 'action' => 'new-project'],
                    ['key' => 'Ctrl + D', 'mac' => 'Cmd + D', 'description' => 'Toggle dark mode', 'action' => 'toggle-theme'],
                    ['key' => 'Ctrl + /', 'mac' => 'Cmd + /', 'description' => 'Show shortcuts help', 'action' => 'show-shortcuts'],
                    ['key' => 'Escape', 'mac' => 'Escape', 'description' => 'Close modal/dialog', 'action' => 'close-modal'],
                ]
            ],
            'navigation' => [
                'title' => 'Navigation',
                'shortcuts' => [
                    ['key' => 'G H', 'mac' => 'G H', 'description' => 'Go to dashboard', 'action' => 'go-dashboard'],
                    ['key' => 'G P', 'mac' => 'G P', 'description' => 'Go to projects', 'action' => 'go-projects'],
                    ['key' => 'G N', 'mac' => 'G N', 'description' => 'Go to notifications', 'action' => 'go-notifications'],
                    ['key' => 'G S', 'mac' => 'G S', 'description' => 'Go to settings', 'action' => 'go-settings'],
                ]
            ],
            'kanban' => [
                'title' => 'Kanban Board',
                'shortcuts' => [
                    ['key' => 'F', 'mac' => 'F', 'description' => 'Toggle filters', 'action' => 'toggle-filters'],
                    ['key' => 'R', 'mac' => 'R', 'description' => 'Refresh board', 'action' => 'refresh-board'],
                    ['key' => 'Ctrl + F', 'mac' => 'Cmd + F', 'description' => 'Focus search', 'action' => 'focus-search'],
                    ['key' => '1-9', 'mac' => '1-9', 'description' => 'Focus status column', 'action' => 'focus-column'],
                ]
            ],
            'task' => [
                'title' => 'Task Management',
                'shortcuts' => [
                    ['key' => 'E', 'mac' => 'E', 'description' => 'Edit selected task', 'action' => 'edit-task'],
                    ['key' => 'Delete', 'mac' => 'Delete', 'description' => 'Delete selected task', 'action' => 'delete-task'],
                    ['key' => 'Enter', 'mac' => 'Enter', 'description' => 'Open task details', 'action' => 'open-task'],
                    ['key' => 'C', 'mac' => 'C', 'description' => 'Add comment', 'action' => 'add-comment'],
                    ['key' => 'A', 'mac' => 'A', 'description' => 'Assign task', 'action' => 'assign-task'],
                ]
            ]
        ];
    }

    /**
     * Get shortcuts by category
     */
    public function getShortcutsByCategory(string $category): array
    {
        $shortcuts = $this->getShortcuts();
        return $shortcuts[$category] ?? [];
    }

    /**
     * Get all shortcuts as flat array
     */
    public function getFlatShortcuts(): array
    {
        $shortcuts = $this->getShortcuts();
        $flat = [];
        
        foreach ($shortcuts as $category => $group) {
            foreach ($group['shortcuts'] as $shortcut) {
                $flat[$shortcut['action']] = $shortcut;
            }
        }
        
        return $flat;
    }

    /**
     * Check if user is on Mac
     */
    public function isMac(): bool
    {
        return str_contains(request()->userAgent(), 'Mac');
    }

    /**
     * Get keyboard shortcut for display
     */
    public function getDisplayKey(array $shortcut): string
    {
        return $this->isMac() ? $shortcut['mac'] : $shortcut['key'];
    }

    /**
     * Generate JavaScript for keyboard shortcuts
     */
    public function generateJavaScript(): string
    {
        $shortcuts = $this->getFlatShortcuts();
        $isMac = $this->isMac();
        
        $js = "
        // Keyboard shortcuts
        document.addEventListener('keydown', function(e) {
            // Don't trigger shortcuts when typing in inputs
            if (['INPUT', 'TEXTAREA', 'SELECT'].includes(e.target.tagName)) {
                return;
            }
            
            const key = e.key;
            const ctrl = " . ($isMac ? 'e.metaKey' : 'e.ctrlKey') . ";
            const shift = e.shiftKey;
            const alt = e.altKey;
            
            // Global shortcuts
            if (ctrl && key === 'k') {
                e.preventDefault();
                window.dispatchEvent(new CustomEvent('keyboard-shortcut', { detail: { action: 'global-search' } }));
                return;
            }
            
            if (ctrl && key === 'n' && !shift) {
                e.preventDefault();
                window.dispatchEvent(new CustomEvent('keyboard-shortcut', { detail: { action: 'new-task' } }));
                return;
            }
            
            if (ctrl && shift && key === 'N') {
                e.preventDefault();
                window.dispatchEvent(new CustomEvent('keyboard-shortcut', { detail: { action: 'new-project' } }));
                return;
            }
            
            if (ctrl && key === 'd') {
                e.preventDefault();
                window.dispatchEvent(new CustomEvent('keyboard-shortcut', { detail: { action: 'toggle-theme' } }));
                return;
            }
            
            if (ctrl && key === '/') {
                e.preventDefault();
                window.dispatchEvent(new CustomEvent('keyboard-shortcut', { detail: { action: 'show-shortcuts' } }));
                return;
            }
            
            if (key === 'Escape') {
                window.dispatchEvent(new CustomEvent('keyboard-shortcut', { detail: { action: 'close-modal' } }));
                return;
            }
            
            // Navigation shortcuts (G + letter)
            if (window.navigationMode && key.toUpperCase() === 'H') {
                window.navigationMode = false;
                window.dispatchEvent(new CustomEvent('keyboard-shortcut', { detail: { action: 'go-dashboard' } }));
                return;
            }
            
            if (window.navigationMode && key.toUpperCase() === 'P') {
                window.navigationMode = false;
                window.dispatchEvent(new CustomEvent('keyboard-shortcut', { detail: { action: 'go-projects' } }));
                return;
            }
            
            if (window.navigationMode && key.toUpperCase() === 'N') {
                window.navigationMode = false;
                window.dispatchEvent(new CustomEvent('keyboard-shortcut', { detail: { action: 'go-notifications' } }));
                return;
            }
            
            if (window.navigationMode && key.toUpperCase() === 'S') {
                window.navigationMode = false;
                window.dispatchEvent(new CustomEvent('keyboard-shortcut', { detail: { action: 'go-settings' } }));
                return;
            }
            
            if (key.toUpperCase() === 'G') {
                window.navigationMode = true;
                setTimeout(() => { window.navigationMode = false; }, 2000); // Reset after 2 seconds
                return;
            }
            
            // Kanban shortcuts
            if (key.toUpperCase() === 'F') {
                window.dispatchEvent(new CustomEvent('keyboard-shortcut', { detail: { action: 'toggle-filters' } }));
                return;
            }
            
            if (key.toUpperCase() === 'R') {
                window.dispatchEvent(new CustomEvent('keyboard-shortcut', { detail: { action: 'refresh-board' } }));
                return;
            }
            
            if (ctrl && key === 'f') {
                e.preventDefault();
                window.dispatchEvent(new CustomEvent('keyboard-shortcut', { detail: { action: 'focus-search' } }));
                return;
            }
            
            // Column focus (1-9)
            if (/^[1-9]$/.test(key)) {
                window.dispatchEvent(new CustomEvent('keyboard-shortcut', { detail: { action: 'focus-column', column: parseInt(key) } }));
                return;
            }
            
            // Task shortcuts (when task is selected)
            if (window.selectedTask) {
                if (key.toUpperCase() === 'E') {
                    window.dispatchEvent(new CustomEvent('keyboard-shortcut', { detail: { action: 'edit-task' } }));
                    return;
                }
                
                if (key === 'Delete') {
                    window.dispatchEvent(new CustomEvent('keyboard-shortcut', { detail: { action: 'delete-task' } }));
                    return;
                }
                
                if (key === 'Enter') {
                    window.dispatchEvent(new CustomEvent('keyboard-shortcut', { detail: { action: 'open-task' } }));
                    return;
                }
                
                if (key.toUpperCase() === 'C') {
                    window.dispatchEvent(new CustomEvent('keyboard-shortcut', { detail: { action: 'add-comment' } }));
                    return;
                }
                
                if (key.toUpperCase() === 'A') {
                    window.dispatchEvent(new CustomEvent('keyboard-shortcut', { detail: { action: 'assign-task' } }));
                    return;
                }
            }
        });
        
        // Initialize navigation mode
        window.navigationMode = false;
        window.selectedTask = null;
        ";
        
        return $js;
    }
}