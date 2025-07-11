<div>
    <div class="py-6">
        <div class="max-w-full mx-auto px-4 sm:px-6 lg:px-8">
            <div class="bg-white overflow-hidden shadow-sm sm:rounded-lg">
                <div class="p-6">
                    <!-- Project Header -->
                    <div class="flex justify-between items-center mb-6">
                        <div class="flex items-center">
                            <div class="w-4 h-4 rounded-full mr-3" style="background-color: {{ $project->color }}"></div>
                            <div>
                                <h1 class="text-2xl font-bold text-gray-900">{{ $project->name }}</h1>
                                @if($project->description)
                                    <p class="text-gray-600 mt-1">{{ $project->description }}</p>
                                @endif
                            </div>
                        </div>
                        
                        <div class="flex space-x-3">
                            <button wire:click="toggleFilters" 
                                    class="relative px-4 py-2 {{ $search || $filterPriority || $filterAssignedTo || $filterStatus || $filterTag || $filterDateFrom || $filterDateTo || $filterCompletionStatus || $filterGitHubStatus ? 'bg-blue-500' : 'bg-gray-500' }} text-white rounded hover:bg-{{ $search || $filterPriority || $filterAssignedTo || $filterStatus || $filterTag || $filterDateFrom || $filterDateTo || $filterCompletionStatus || $filterGitHubStatus ? 'blue' : 'gray' }}-600 transition-colors">
                                <svg class="w-4 h-4 inline mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 4a1 1 0 011-1h16a1 1 0 011 1v2.586a1 1 0 01-.293.707l-6.414 6.414a1 1 0 00-.293.707V17l-4 4v-6.586a1 1 0 00-.293-.707L3.293 7.207A1 1 0 013 6.5V4z"></path>
                                </svg>
                                Filtrele
                                @php
                                    $activeFilters = collect([$search, $filterPriority, $filterAssignedTo, $filterStatus, $filterTag, $filterDateFrom, $filterDateTo, $filterCompletionStatus, $filterGitHubStatus])->filter()->count();
                                @endphp
                                @if($activeFilters > 0)
                                    <span class="absolute top-0 right-0 inline-flex items-center justify-center px-2 py-1 text-xs font-bold leading-none text-red-100 transform translate-x-1/2 -translate-y-1/2 bg-red-600 rounded-full">
                                        {{ $activeFilters }}
                                    </span>
                                @endif
                            </button>
                            @can('create tasks')
                                <button wire:click="openTaskForm" 
                                        class="px-4 py-2 bg-blue-500 text-white rounded hover:bg-blue-600 transition-colors">
                                    + Yeni Görev
                                </button>
                            @endcan
                            <a href="/projects/{{ $project->id }}/sync" class="px-4 py-2 bg-indigo-500 text-white rounded hover:bg-indigo-600 transition-colors">
                                GitHub Sync
                            </a>
                            <a href="/projects/{{ $project->id }}/github" class="px-4 py-2 bg-green-500 text-white rounded hover:bg-green-600 transition-colors">
                                GitHub
                            </a>
                            <a href="/projects/{{ $project->id }}/analytics" class="px-4 py-2 bg-orange-500 text-white rounded hover:bg-orange-600 transition-colors">
                                Analitikler
                            </a>
                            <a href="/projects/{{ $project->id }}/reports" class="px-4 py-2 bg-teal-500 text-white rounded hover:bg-teal-600 transition-colors">
                                Raporlar
                            </a>
                            <a href="/projects/{{ $project->id }}/settings" class="px-4 py-2 bg-purple-500 text-white rounded hover:bg-purple-600 transition-colors">
                                Ayarlar
                            </a>
                            <a href="/dashboard" class="px-4 py-2 bg-gray-500 text-white rounded hover:bg-gray-600 transition-colors">
                                Geri Dön
                            </a>
                        </div>
                    </div>

                    <!-- Search and Filters -->
                    @if($showFilters)
                        <div class="bg-gray-50 rounded-lg p-4 mb-6">
                            <!-- Basic Filters -->
                            <div class="grid grid-cols-1 md:grid-cols-4 gap-4 mb-4">
                                <!-- Search -->
                                <div>
                                    <label class="block text-sm font-medium text-gray-700 mb-2">Arama</label>
                                    <input wire:model.live="search" 
                                           type="text" 
                                           placeholder="Görev, kullanıcı, etiket ara..." 
                                           class="w-full px-3 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-2 focus:ring-blue-500">
                                </div>

                                <!-- Priority Filter -->
                                <div>
                                    <label class="block text-sm font-medium text-gray-700 mb-2">Öncelik</label>
                                    <select wire:model.live="filterPriority" class="w-full px-3 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-2 focus:ring-blue-500">
                                        <option value="">Tüm Öncelikler</option>
                                        <option value="urgent">Acil</option>
                                        <option value="high">Yüksek</option>
                                        <option value="medium">Orta</option>
                                        <option value="low">Düşük</option>
                                    </select>
                                </div>

                                <!-- Assigned User Filter -->
                                <div>
                                    <label class="block text-sm font-medium text-gray-700 mb-2">Atanan Kişi</label>
                                    <select wire:model.live="filterAssignedTo" class="w-full px-3 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-2 focus:ring-blue-500">
                                        <option value="">Herkesi Göster</option>
                                        <option value="me">Bana Atanan</option>
                                        <option value="unassigned">Atanmamış</option>
                                        @foreach($projectUsers as $user)
                                            <option value="{{ $user->id }}">{{ $user->name }}</option>
                                        @endforeach
                                    </select>
                                </div>

                                <!-- Status Filter -->
                                <div>
                                    <label class="block text-sm font-medium text-gray-700 mb-2">Durum</label>
                                    <select wire:model.live="filterStatus" class="w-full px-3 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-2 focus:ring-blue-500">
                                        <option value="">Tüm Durumlar</option>
                                        @foreach($taskStatuses as $status)
                                            <option value="{{ $status->id }}">{{ $status->name }}</option>
                                        @endforeach
                                    </select>
                                </div>
                            </div>

                            <!-- Advanced Filters Toggle -->
                            <div class="mb-4">
                                <button wire:click="toggleAdvancedFilters" 
                                        class="text-sm text-blue-600 hover:text-blue-800 font-medium">
                                    {{ $showAdvancedFilters ? '- Gelişmiş Filtreleri Gizle' : '+ Gelişmiş Filtreler' }}
                                </button>
                            </div>

                            <!-- Advanced Filters -->
                            @if($showAdvancedFilters)
                                <div class="border-t border-gray-200 pt-4 mb-4">
                                    <div class="grid grid-cols-1 md:grid-cols-3 gap-4 mb-4">
                                        <!-- Tag Filter -->
                                        <div>
                                            <label class="block text-sm font-medium text-gray-700 mb-2">Etiket</label>
                                            <select wire:model.live="filterTag" class="w-full px-3 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-2 focus:ring-blue-500">
                                                <option value="">Tüm Etiketler</option>
                                                @foreach($projectTags as $tag)
                                                    <option value="{{ $tag->id }}">{{ $tag->name }}</option>
                                                @endforeach
                                            </select>
                                        </div>

                                        <!-- Completion Status Filter -->
                                        <div>
                                            <label class="block text-sm font-medium text-gray-700 mb-2">Tamamlanma Durumu</label>
                                            <select wire:model.live="filterCompletionStatus" class="w-full px-3 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-2 focus:ring-blue-500">
                                                <option value="">Tümü</option>
                                                <option value="completed">Tamamlanan</option>
                                                <option value="pending">Bekleyen</option>
                                                <option value="overdue">Geciken</option>
                                            </select>
                                        </div>

                                        <!-- GitHub Status Filter -->
                                        <div>
                                            <label class="block text-sm font-medium text-gray-700 mb-2">GitHub Durumu</label>
                                            <select wire:model.live="filterGitHubStatus" class="w-full px-3 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-2 focus:ring-blue-500">
                                                <option value="">Tümü</option>
                                                <option value="github">GitHub'dan</option>
                                                <option value="manual">Manuel</option>
                                            </select>
                                        </div>
                                    </div>

                                    <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                                        <!-- Date From Filter -->
                                        <div>
                                            <label class="block text-sm font-medium text-gray-700 mb-2">Tarih (Başlangıç)</label>
                                            <input wire:model.live="filterDateFrom" 
                                                   type="date" 
                                                   class="w-full px-3 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-2 focus:ring-blue-500">
                                        </div>

                                        <!-- Date To Filter -->
                                        <div>
                                            <label class="block text-sm font-medium text-gray-700 mb-2">Tarih (Bitiş)</label>
                                            <input wire:model.live="filterDateTo" 
                                                   type="date" 
                                                   class="w-full px-3 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-2 focus:ring-blue-500">
                                        </div>
                                    </div>
                                </div>
                            @endif

                            <!-- Sorting Options -->
                            <div class="border-t border-gray-200 pt-4 mb-4">
                                <label class="block text-sm font-medium text-gray-700 mb-2">Sıralama</label>
                                <div class="flex flex-wrap gap-2">
                                    <button wire:click="toggleSort('title')" 
                                            class="px-3 py-1 text-xs rounded {{ $sortBy === 'title' ? 'bg-blue-500 text-white' : 'bg-gray-200 text-gray-700 hover:bg-gray-300' }}">
                                        Başlık {{ $sortBy === 'title' ? ($sortDirection === 'asc' ? '↑' : '↓') : '' }}
                                    </button>
                                    <button wire:click="toggleSort('priority')" 
                                            class="px-3 py-1 text-xs rounded {{ $sortBy === 'priority' ? 'bg-blue-500 text-white' : 'bg-gray-200 text-gray-700 hover:bg-gray-300' }}">
                                        Öncelik {{ $sortBy === 'priority' ? ($sortDirection === 'asc' ? '↑' : '↓') : '' }}
                                    </button>
                                    <button wire:click="toggleSort('due_date')" 
                                            class="px-3 py-1 text-xs rounded {{ $sortBy === 'due_date' ? 'bg-blue-500 text-white' : 'bg-gray-200 text-gray-700 hover:bg-gray-300' }}">
                                        Teslim Tarihi {{ $sortBy === 'due_date' ? ($sortDirection === 'asc' ? '↑' : '↓') : '' }}
                                    </button>
                                    <button wire:click="toggleSort('assigned_to')" 
                                            class="px-3 py-1 text-xs rounded {{ $sortBy === 'assigned_to' ? 'bg-blue-500 text-white' : 'bg-gray-200 text-gray-700 hover:bg-gray-300' }}">
                                        Atanan {{ $sortBy === 'assigned_to' ? ($sortDirection === 'asc' ? '↑' : '↓') : '' }}
                                    </button>
                                    <button wire:click="toggleSort('created_at')" 
                                            class="px-3 py-1 text-xs rounded {{ $sortBy === 'created_at' ? 'bg-blue-500 text-white' : 'bg-gray-200 text-gray-700 hover:bg-gray-300' }}">
                                        Oluşturulma {{ $sortBy === 'created_at' ? ($sortDirection === 'asc' ? '↑' : '↓') : '' }}
                                    </button>
                                </div>
                            </div>

                            <!-- Filter Actions -->
                            <div class="flex justify-between items-center">
                                <div class="text-sm text-gray-600">
                                    @php
                                        $totalTasks = $taskStatuses->sum(function($status) { return $status->tasks->count(); });
                                    @endphp
                                    Toplam {{ $totalTasks }} görev gösteriliyor
                                </div>
                                <button wire:click="clearFilters" 
                                        class="px-3 py-1 text-sm bg-gray-200 text-gray-700 rounded hover:bg-gray-300 transition-colors">
                                    Filtreleri Temizle
                                </button>
                            </div>
                        </div>
                    @endif

                    <!-- Kanban Board -->
                    <div class="overflow-x-auto">
                        <div class="flex space-x-4 min-w-max pb-4">
                            @foreach($taskStatuses as $status)
                                <div class="flex-shrink-0 w-80">
                                    <!-- Status Header -->
                                    <div class="bg-gray-50 rounded-t-lg p-4 border-l-4" style="border-left-color: {{ $status->color }}">
                                        <div class="flex justify-between items-center">
                                            <h3 class="font-semibold text-gray-800">{{ $status->name }}</h3>
                                            <span class="bg-gray-200 text-gray-700 text-xs px-2 py-1 rounded-full">
                                                {{ $status->tasks->count() }}
                                            </span>
                                        </div>
                                    </div>

                                    <!-- Tasks Container -->
                                    <div class="bg-gray-100 rounded-b-lg p-2 min-h-[400px] border border-t-0 border-gray-200 task-container"
                                         data-status-id="{{ $status->id }}"
                                         ondrop="drop(event)" 
                                         ondragover="allowDrop(event)">
                                        
                                        @foreach($status->tasks as $task)
                                            <!-- Task Card -->
                                            <div data-task-id="{{ $task->id }}"
                                                 wire:key="task-{{ $task->id }}"
                                                 draggable="true"
                                                 ondragstart="drag(event)"
                                                 class="bg-white rounded-lg p-4 mb-3 shadow-sm border hover:shadow-md transition-shadow cursor-move task-card">
                                                
                                                <!-- Task Header -->
                                                <div class="flex justify-between items-start mb-2">
                                                    <h4 class="font-medium text-gray-900 line-clamp-2">{{ $task->title }}</h4>
                                                    <div class="flex space-x-1 ml-2">
                                                        <button onclick="@this.dispatch('open-task-detail', {taskId: {{ $task->id }}})" 
                                                                class="text-gray-400 hover:text-green-600 transition-colors">
                                                            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z"></path>
                                                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M2.458 12C3.732 7.943 7.523 5 12 5c4.478 0 8.268 2.943 9.542 7-1.274 4.057-5.064 7-9.542 7-4.477 0-8.268-2.943-9.542-7z"></path>
                                                            </svg>
                                                        </button>
                                                        @can('edit tasks')
                                                            <button wire:click="editTask({{ $task->id }})" 
                                                                    class="text-gray-400 hover:text-blue-600 transition-colors">
                                                                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M11 5H6a2 2 0 00-2 2v11a2 2 0 002 2h11a2 2 0 002-2v-5m-1.414-9.414a2 2 0 112.828 2.828L11.828 15H9v-2.828l8.586-8.586z"></path>
                                                                </svg>
                                                            </button>
                                                        @endcan
                                                        @can('delete tasks')
                                                            <button wire:click="deleteTask({{ $task->id }})"
                                                                    wire:confirm="Bu görevi silmek istediğinizden emin misiniz?"
                                                                    class="text-gray-400 hover:text-red-600 transition-colors">
                                                                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16"></path>
                                                                </svg>
                                                            </button>
                                                        @endcan
                                                    </div>
                                                </div>

                                                <!-- Task Description -->
                                                @if($task->description)
                                                    <p class="text-sm text-gray-600 mb-3 line-clamp-2">{{ $task->description }}</p>
                                                @endif

                                                <!-- Task Meta Information -->
                                                <div class="space-y-2">
                                                    <!-- Priority Badge -->
                                                    <div class="flex items-center justify-between">
                                                        <span class="inline-flex items-center px-2 py-1 rounded-full text-xs font-medium
                                                            @if($task->priority === 'urgent') bg-red-100 text-red-800
                                                            @elseif($task->priority === 'high') bg-orange-100 text-orange-800
                                                            @elseif($task->priority === 'medium') bg-yellow-100 text-yellow-800
                                                            @else bg-green-100 text-green-800
                                                            @endif">
                                                            {{ ucfirst($task->priority) }}
                                                        </span>

                                                        @if($task->due_date)
                                                            <span class="text-xs text-gray-500 
                                                                @if($task->is_overdue) text-red-600 font-medium @endif">
                                                                {{ $task->due_date->format('d.m.Y') }}
                                                            </span>
                                                        @endif
                                                    </div>

                                                    <!-- Assigned User -->
                                                    @if($task->assignedUser)
                                                        <div class="flex items-center text-xs text-gray-600">
                                                            <svg class="w-3 h-3 mr-1" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M16 7a4 4 0 11-8 0 4 4 0 018 0zM12 14a7 7 0 00-7 7h14a7 7 0 00-7-7z"></path>
                                                            </svg>
                                                            {{ $task->assignedUser->name }}
                                                        </div>
                                                    @endif

                                                    <!-- Subtasks Count -->
                                                    @if($task->subtasks()->count() > 0)
                                                        <div class="flex items-center text-xs text-gray-600">
                                                            <svg class="w-3 h-3 mr-1" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5H7a2 2 0 00-2 2v10a2 2 0 002 2h8a2 2 0 002-2V7a2 2 0 00-2-2h-2M9 5a2 2 0 002 2h2a2 2 0 002-2M9 5a2 2 0 012-2h2a2 2 0 012 2"></path>
                                                            </svg>
                                                            {{ $task->subtasks()->where('completed_at', '!=', null)->count() }}/{{ $task->subtasks()->count() }} alt görev
                                                        </div>
                                                    @endif

                                                    <!-- GitHub Badge -->
                                                    @if($task->isFromGitHub())
                                                        <div class="flex items-center text-xs text-gray-600">
                                                            <svg class="w-3 h-3 mr-1" fill="currentColor" viewBox="0 0 20 20">
                                                                <path fill-rule="evenodd" d="M10 0C4.477 0 0 4.484 0 10.017c0 4.425 2.865 8.18 6.839 9.504.5.092.682-.217.682-.483 0-.237-.008-.868-.013-1.703-2.782.605-3.369-1.343-3.369-1.343-.454-1.158-1.11-1.466-1.11-1.466-.908-.62.069-.608.069-.608 1.003.07 1.531 1.032 1.531 1.032.892 1.53 2.341 1.088 2.91.832.092-.647.35-1.088.636-1.338-2.22-.253-4.555-1.113-4.555-4.951 0-1.093.39-1.988 1.029-2.688-.103-.253-.446-1.272.098-2.65 0 0 .84-.27 2.75 1.026A9.564 9.564 0 0110 4.844c.85.004 1.705.115 2.504.337 1.909-1.296 2.747-1.027 2.747-1.027.546 1.379.203 2.398.1 2.651.64.7 1.028 1.595 1.028 2.688 0 3.848-2.339 4.695-4.566 4.942.359.31.678.921.678 1.856 0 1.338-.012 2.419-.012 2.747 0 .268.18.58.688.482A10.019 10.019 0 0020 10.017C20 4.484 15.522 0 10 0z" clip-rule="evenodd"/>
                                                            </svg>
                                                            GitHub
                                                        </div>
                                                    @endif
                                                </div>

                                                <!-- Subtask Manager (only for main tasks) -->
                                                @if(!$task->is_subtask)
                                                    <livewire:subtask-manager :task="$task" wire:key="subtask-manager-{{ $task->id }}" />
                                                @endif
                                            </div>
                                        @endforeach

                                        <!-- Add Task Button for this Status -->
                                        @can('create tasks')
                                            <button wire:click="openTaskForm" 
                                                    class="w-full p-3 border-2 border-dashed border-gray-300 rounded-lg text-gray-500 hover:border-gray-400 hover:text-gray-600 transition-colors">
                                                + Görev Ekle
                                            </button>
                                        @endcan
                                    </div>
                                </div>
                            @endforeach
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <!-- Task Form Modal -->
        @if($showTaskForm)
            <livewire:task-form 
                :project="$project" 
                :task="$editingTask"
                :show-modal="true"
                wire:key="task-form-{{ $editingTask ? $editingTask->id : 'new' }}" />
        @endif

        <!-- Task Detail Modal -->
        <livewire:task-detail wire:key="task-detail" />
    </div>

    <!-- Embedded Styles and Scripts -->
    <style>
        .line-clamp-2 {
            display: -webkit-box;
            -webkit-line-clamp: 2;
            -webkit-box-orient: vertical;
            overflow: hidden;
        }
        .task-container.drag-over {
            background-color: #e5e7eb;
            border: 2px dashed #9ca3af;
        }
    </style>

    <script>
        function allowDrop(ev) {
            ev.preventDefault();
            ev.currentTarget.classList.add('drag-over');
        }

        function drag(ev) {
            ev.dataTransfer.setData("text", ev.currentTarget.getAttribute('data-task-id'));
        }

        function drop(ev) {
            ev.preventDefault();
            ev.currentTarget.classList.remove('drag-over');
            
            const taskId = ev.dataTransfer.getData("text");
            const newStatusId = ev.currentTarget.getAttribute('data-status-id');
            
            // Calculate position based on drop location
            const container = ev.currentTarget;
            const afterElement = getDragAfterElement(container, ev.clientY);
            const position = afterElement ? Array.from(container.children).indexOf(afterElement) : container.children.length - 1;
            
            // Call Livewire method
            @this.call('moveTask', taskId, newStatusId, position);
        }

        function getDragAfterElement(container, y) {
            const draggableElements = [...container.querySelectorAll('.task-card:not(.dragging)')];
            
            return draggableElements.reduce((closest, child) => {
                const box = child.getBoundingClientRect();
                const offset = y - box.top - box.height / 2;
                
                if (offset < 0 && offset > closest.offset) {
                    return { offset: offset, element: child };
                } else {
                    return closest;
                }
            }, { offset: Number.NEGATIVE_INFINITY }).element;
        }

        // Remove drag-over class when dragging leaves
        document.addEventListener('dragleave', function(e) {
            if (e.target.classList.contains('task-container')) {
                e.target.classList.remove('drag-over');
            }
        });
    </script>
</div>
