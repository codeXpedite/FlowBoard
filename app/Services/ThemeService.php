<?php

namespace App\Services;

use Illuminate\Support\Facades\Cookie;

class ThemeService
{
    const LIGHT_THEME = 'light';
    const DARK_THEME = 'dark';
    const COOKIE_NAME = 'theme_preference';
    const COOKIE_DURATION = 365 * 24 * 60; // 1 year in minutes

    /**
     * Get current theme preference
     */
    public function getCurrentTheme(): string
    {
        // Check user preference first
        if (auth()->check() && auth()->user()->theme_preference) {
            return auth()->user()->theme_preference;
        }

        // Check cookie
        $cookieTheme = Cookie::get(self::COOKIE_NAME);
        if ($cookieTheme && in_array($cookieTheme, [self::LIGHT_THEME, self::DARK_THEME])) {
            return $cookieTheme;
        }

        // Default to light theme
        return self::LIGHT_THEME;
    }

    /**
     * Set theme preference
     */
    public function setTheme(string $theme): void
    {
        if (!in_array($theme, [self::LIGHT_THEME, self::DARK_THEME])) {
            throw new \InvalidArgumentException('Invalid theme: ' . $theme);
        }

        // Save to user profile if authenticated
        if (auth()->check()) {
            auth()->user()->update(['theme_preference' => $theme]);
        }

        // Set cookie for non-authenticated users or as backup
        Cookie::queue(self::COOKIE_NAME, $theme, self::COOKIE_DURATION);
    }

    /**
     * Toggle between light and dark theme
     */
    public function toggleTheme(): string
    {
        $currentTheme = $this->getCurrentTheme();
        $newTheme = $currentTheme === self::LIGHT_THEME ? self::DARK_THEME : self::LIGHT_THEME;
        
        $this->setTheme($newTheme);
        
        return $newTheme;
    }

    /**
     * Get theme CSS classes
     */
    public function getThemeClasses(): array
    {
        $theme = $this->getCurrentTheme();
        
        return [
            'body' => $theme === self::DARK_THEME ? 'dark bg-gray-900 text-white' : 'bg-white text-gray-900',
            'card' => $theme === self::DARK_THEME ? 'bg-gray-800 border-gray-700' : 'bg-white border-gray-200',
            'input' => $theme === self::DARK_THEME ? 'bg-gray-700 border-gray-600 text-white placeholder-gray-400' : 'bg-white border-gray-300 text-gray-900',
            'button_primary' => $theme === self::DARK_THEME ? 'bg-blue-600 hover:bg-blue-700' : 'bg-blue-500 hover:bg-blue-600',
            'button_secondary' => $theme === self::DARK_THEME ? 'bg-gray-600 hover:bg-gray-700' : 'bg-gray-500 hover:bg-gray-600',
            'text_primary' => $theme === self::DARK_THEME ? 'text-white' : 'text-gray-900',
            'text_secondary' => $theme === self::DARK_THEME ? 'text-gray-300' : 'text-gray-600',
            'border' => $theme === self::DARK_THEME ? 'border-gray-700' : 'border-gray-200',
            'hover' => $theme === self::DARK_THEME ? 'hover:bg-gray-700' : 'hover:bg-gray-50',
        ];
    }

    /**
     * Get available themes
     */
    public function getAvailableThemes(): array
    {
        return [
            self::LIGHT_THEME => [
                'name' => 'Light',
                'icon' => 'sun',
                'description' => 'Light theme for better readability in bright environments'
            ],
            self::DARK_THEME => [
                'name' => 'Dark',
                'icon' => 'moon',
                'description' => 'Dark theme for reduced eye strain in low-light environments'
            ],
        ];
    }

    /**
     * Check if current theme is dark
     */
    public function isDarkTheme(): bool
    {
        return $this->getCurrentTheme() === self::DARK_THEME;
    }

    /**
     * Get theme CSS variables
     */
    public function getThemeVariables(): array
    {
        $theme = $this->getCurrentTheme();
        
        if ($theme === self::DARK_THEME) {
            return [
                '--bg-primary' => '#111827',     // gray-900
                '--bg-secondary' => '#1f2937',   // gray-800
                '--bg-tertiary' => '#374151',    // gray-700
                '--text-primary' => '#ffffff',
                '--text-secondary' => '#d1d5db', // gray-300
                '--text-tertiary' => '#9ca3af',  // gray-400
                '--border-color' => '#374151',   // gray-700
                '--accent-color' => '#3b82f6',   // blue-500
                '--success-color' => '#10b981',  // emerald-500
                '--warning-color' => '#f59e0b',  // amber-500
                '--error-color' => '#ef4444',    // red-500
            ];
        }
        
        return [
            '--bg-primary' => '#ffffff',
            '--bg-secondary' => '#f9fafb',   // gray-50
            '--bg-tertiary' => '#f3f4f6',    // gray-100
            '--text-primary' => '#111827',   // gray-900
            '--text-secondary' => '#6b7280', // gray-500
            '--text-tertiary' => '#9ca3af',  // gray-400
            '--border-color' => '#e5e7eb',   // gray-200
            '--accent-color' => '#3b82f6',   // blue-500
            '--success-color' => '#10b981',  // emerald-500
            '--warning-color' => '#f59e0b',  // amber-500
            '--error-color' => '#ef4444',    // red-500
        ];
    }
}