<?php

use Illuminate\Support\Facades\Route;
use App\Livewire\KanbanBoard;
use App\Livewire\ProjectSettings;

Route::view('/', 'welcome');

Route::view('dashboard', 'dashboard')
    ->middleware(['auth', 'verified'])
    ->name('dashboard');

Route::get('projects/{project}', KanbanBoard::class)
    ->middleware(['auth', 'verified'])
    ->name('projects.show');

Route::get('projects/{project}/settings', ProjectSettings::class)
    ->middleware(['auth', 'verified'])
    ->name('projects.settings');

Route::get('projects/{project}/github', \App\Livewire\GitHubRepositories::class)
    ->middleware(['auth', 'verified'])
    ->name('projects.github');

Route::get('projects/{project}/webhooks', \App\Livewire\WebhookDashboard::class)
    ->middleware(['auth', 'verified'])
    ->name('projects.webhooks');

Route::get('projects/{project}/sync', \App\Livewire\GitHubSync::class)
    ->middleware(['auth', 'verified'])
    ->name('projects.sync');

Route::get('projects/{project}/analytics', \App\Livewire\ProjectAnalytics::class)
    ->middleware(['auth', 'verified'])
    ->name('projects.analytics');

Route::get('projects/{project}/reports', \App\Livewire\ProjectReports::class)
    ->middleware(['auth', 'verified'])
    ->name('projects.reports');

Route::get('notifications/settings', \App\Livewire\NotificationSettings::class)
    ->middleware(['auth', 'verified'])
    ->name('notifications.settings');

Route::get('templates', \App\Livewire\ProjectTemplates::class)
    ->middleware(['auth', 'verified'])
    ->name('templates.index');

Route::get('users', \App\Livewire\UserManagement::class)
    ->middleware(['auth', 'verified', 'permission:manage users'])
    ->name('users.index');

Route::view('profile', 'profile')
    ->middleware(['auth'])
    ->name('profile');

require __DIR__.'/auth.php';
require __DIR__.'/api.php';
