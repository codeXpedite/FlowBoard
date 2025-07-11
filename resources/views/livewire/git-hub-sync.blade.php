<div class="py-6">
    <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
        <!-- Header -->
        <div class="bg-white overflow-hidden shadow-sm sm:rounded-lg mb-6">
            <div class="p-6">
                <div class="flex justify-between items-center mb-4">
                    <div>
                        <h2 class="text-2xl font-bold text-gray-900">GitHub Synchronization</h2>
                        <p class="text-gray-600">{{ $project->name }} projesi GitHub issue senkronizasyonu</p>
                    </div>
                    <div class="flex space-x-3">
                        <a href="/projects/{{ $project->id }}/webhooks" 
                           class="px-4 py-2 bg-purple-500 text-white rounded hover:bg-purple-600 transition-colors">
                            Webhook Dashboard
                        </a>
                        <a href="/projects/{{ $project->id }}/github" 
                           class="px-4 py-2 bg-green-500 text-white rounded hover:bg-green-600 transition-colors">
                            Repository Yönetimi
                        </a>
                        <a href="/projects/{{ $project->id }}" 
                           class="px-4 py-2 bg-blue-500 text-white rounded hover:bg-blue-600 transition-colors">
                            Kanban Board
                        </a>
                    </div>
                </div>

                <!-- Success/Error Messages -->
                @if (session()->has('message'))
                    <div class="bg-green-100 border border-green-400 text-green-700 px-4 py-3 rounded mb-4">
                        {{ session('message') }}
                    </div>
                @endif

                @if (session()->has('error'))
                    <div class="bg-red-100 border border-red-400 text-red-700 px-4 py-3 rounded mb-4">
                        {{ session('error') }}
                    </div>
                @endif

                <!-- Sync Configuration -->
                <div class="grid grid-cols-1 md:grid-cols-2 gap-6 mb-6">
                    <!-- GitHub Token -->
                    <div>
                        <label for="githubToken" class="block text-sm font-medium text-gray-700 mb-2">
                            GitHub Personal Access Token
                        </label>
                        <input type="password" 
                               id="githubToken" 
                               wire:model="githubToken" 
                               placeholder="ghp_xxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxx"
                               class="w-full border border-gray-300 rounded-md shadow-sm py-2 px-3 focus:outline-none focus:ring-blue-500 focus:border-blue-500">
                        <p class="text-xs text-gray-500 mt-1">GitHub API erişimi için gerekli.</p>
                        @error('githubToken') 
                            <span class="text-red-500 text-sm">{{ $message }}</span> 
                        @enderror
                    </div>

                    <!-- Repository Selection -->
                    <div>
                        <label for="selectedRepository" class="block text-sm font-medium text-gray-700 mb-2">
                            Repository Seçin
                        </label>
                        <select wire:model.live="selectedRepository" 
                                id="selectedRepository"
                                class="w-full border border-gray-300 rounded-md shadow-sm py-2 px-3 focus:outline-none focus:ring-blue-500 focus:border-blue-500">
                            <option value="">Repository seçin...</option>
                            @foreach($repositories as $repo)
                                <option value="{{ $repo->id }}">{{ $repo->full_name }}</option>
                            @endforeach
                        </select>
                        @error('selectedRepository') 
                            <span class="text-red-500 text-sm">{{ $message }}</span> 
                        @enderror
                    </div>
                </div>

                <!-- Sync Statistics -->
                @if($syncStats)
                    <div class="grid grid-cols-2 md:grid-cols-4 gap-4 mb-6">
                        <div class="bg-blue-50 p-4 rounded-lg text-center">
                            <div class="text-2xl font-bold text-blue-600">{{ $syncStats['total_tasks'] }}</div>
                            <div class="text-sm text-blue-700">Toplam Task</div>
                        </div>
                        <div class="bg-green-50 p-4 rounded-lg text-center">
                            <div class="text-2xl font-bold text-green-600">{{ $syncStats['github_linked'] }}</div>
                            <div class="text-sm text-green-700">GitHub Bağlı</div>
                        </div>
                        <div class="bg-yellow-50 p-4 rounded-lg text-center">
                            <div class="text-2xl font-bold text-yellow-600">{{ $syncStats['local_only'] }}</div>
                            <div class="text-sm text-yellow-700">Sadece Lokal</div>
                        </div>
                        <div class="bg-purple-50 p-4 rounded-lg text-center">
                            <div class="text-sm text-purple-700">Son Sync</div>
                            <div class="text-sm font-medium text-purple-600">{{ $syncStats['last_sync'] ?: 'Henüz sync yapılmamış' }}</div>
                        </div>
                    </div>
                @endif

                <!-- Sync Actions -->
                <div class="flex flex-wrap gap-3 mb-6">
                    <button wire:click="loadIssues" 
                            wire:loading.attr="disabled"
                            class="px-4 py-2 bg-blue-600 text-white rounded hover:bg-blue-700 disabled:opacity-50">
                        <span wire:loading.remove wire:target="loadIssues">GitHub Issue'larını Yükle</span>
                        <span wire:loading wire:target="loadIssues">Yükleniyor...</span>
                    </button>

                    @if($githubToken && $selectedRepository)
                        <button wire:click="syncAllIssues" 
                                wire:loading.attr="disabled"
                                onclick="return confirm('Bu işlem tüm GitHub issue\'larını senkronize edecek. Devam etmek istediğinizden emin misiniz?')"
                                class="px-4 py-2 bg-green-600 text-white rounded hover:bg-green-700 disabled:opacity-50">
                            <span wire:loading.remove wire:target="syncAllIssues">Tam Senkronizasyon</span>
                            <span wire:loading wire:target="syncAllIssues">Senkronize ediliyor...</span>
                        </button>
                    @endif

                    <!-- Issue State Filter -->
                    <select wire:model.live="issueState" 
                            class="border border-gray-300 rounded-md shadow-sm py-2 px-3 focus:outline-none focus:ring-blue-500 focus:border-blue-500">
                        <option value="open">Açık Issue'lar</option>
                        <option value="closed">Kapalı Issue'lar</option>
                        <option value="all">Tüm Issue'lar</option>
                    </select>
                </div>
            </div>
        </div>

        <!-- GitHub Issues Preview -->
        @if($showIssuePreview && !empty($availableIssues))
            <div class="bg-white overflow-hidden shadow-sm sm:rounded-lg mb-6">
                <div class="p-6">
                    <div class="flex justify-between items-center mb-4">
                        <h3 class="text-lg font-semibold">GitHub Issue'ları ({{ count($availableIssues) }})</h3>
                        <div class="flex space-x-2">
                            <button wire:click="selectAllIssues" 
                                    class="px-3 py-1 text-sm bg-blue-500 text-white rounded hover:bg-blue-600">
                                Tümünü Seç
                            </button>
                            <button wire:click="clearSelection" 
                                    class="px-3 py-1 text-sm bg-gray-500 text-white rounded hover:bg-gray-600">
                                Seçimi Temizle
                            </button>
                            @if(!empty($selectedIssues))
                                <button wire:click="importSelectedIssues" 
                                        wire:loading.attr="disabled"
                                        class="px-3 py-1 text-sm bg-green-500 text-white rounded hover:bg-green-600 disabled:opacity-50">
                                    <span wire:loading.remove wire:target="importSelectedIssues">Seçilenleri Import Et ({{ count($selectedIssues) }})</span>
                                    <span wire:loading wire:target="importSelectedIssues">Import ediliyor...</span>
                                </button>
                            @endif
                        </div>
                    </div>

                    <div class="max-h-96 overflow-y-auto">
                        @foreach($availableIssues as $issue)
                            <div class="border border-gray-200 rounded-lg p-4 mb-3 {{ $issue['already_imported'] ? 'bg-gray-50 opacity-75' : 'bg-white' }}">
                                <div class="flex items-start justify-between">
                                    <div class="flex items-start space-x-3 flex-1">
                                        @if(!$issue['already_imported'])
                                            <input type="checkbox" 
                                                   wire:change="toggleIssueSelection({{ $issue['id'] }})"
                                                   @if(in_array($issue['id'], $selectedIssues)) checked @endif
                                                   class="mt-1 h-4 w-4 text-blue-600 focus:ring-blue-500 border-gray-300 rounded">
                                        @endif
                                        
                                        <div class="flex-1">
                                            <div class="flex items-center space-x-2">
                                                <h4 class="font-medium text-gray-900">
                                                    <a href="{{ $issue['html_url'] }}" target="_blank" class="text-blue-600 hover:text-blue-800">
                                                        #{{ $issue['number'] }} {{ $issue['title'] }}
                                                    </a>
                                                </h4>
                                                @if($issue['state'] === 'closed')
                                                    <span class="bg-red-100 text-red-800 text-xs px-2 py-1 rounded">Kapalı</span>
                                                @else
                                                    <span class="bg-green-100 text-green-800 text-xs px-2 py-1 rounded">Açık</span>
                                                @endif
                                                @if($issue['already_imported'])
                                                    <span class="bg-blue-100 text-blue-800 text-xs px-2 py-1 rounded">Zaten Import Edilmiş</span>
                                                @endif
                                            </div>
                                            
                                            @if($issue['body'])
                                                <p class="text-gray-600 text-sm mt-1">{{ Str::limit(strip_tags($issue['body']), 100) }}</p>
                                            @endif
                                            
                                            <div class="flex items-center space-x-4 mt-2 text-xs text-gray-500">
                                                <span>{{ \Carbon\Carbon::parse($issue['created_at'])->format('d.m.Y') }}</span>
                                                @if(!empty($issue['labels']))
                                                    <div class="flex space-x-1">
                                                        @foreach(array_slice($issue['labels'], 0, 3) as $label)
                                                            <span class="bg-gray-100 text-gray-700 px-2 py-1 rounded text-xs">{{ $label['name'] }}</span>
                                                        @endforeach
                                                    </div>
                                                @endif
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        @endforeach
                    </div>
                </div>
            </div>
        @endif

        <!-- Local Tasks -->
        @if($selectedRepository && $localTasks->count() > 0)
            <div class="bg-white overflow-hidden shadow-sm sm:rounded-lg">
                <div class="p-6">
                    <h3 class="text-lg font-semibold mb-4">Sadece Lokal Task'lar ({{ $localTasks->total() }})</h3>
                    <p class="text-gray-600 text-sm mb-4">Bu task'lar henüz GitHub ile bağlı değil. GitHub'a göndermek için tıklayın.</p>
                    
                    <div class="space-y-3">
                        @foreach($localTasks as $task)
                            <div class="border border-gray-200 rounded-lg p-4 flex items-center justify-between">
                                <div class="flex-1">
                                    <div class="flex items-center space-x-3">
                                        <h4 class="font-medium text-gray-900">{{ $task->title }}</h4>
                                        <span class="px-2 py-1 text-xs rounded-full {{ 
                                            $task->priority === 'high' ? 'bg-red-100 text-red-800' :
                                            ($task->priority === 'medium' ? 'bg-yellow-100 text-yellow-800' : 'bg-green-100 text-green-800')
                                        }}">{{ ucfirst($task->priority) }}</span>
                                        <span class="bg-blue-100 text-blue-800 text-xs px-2 py-1 rounded">{{ $task->taskStatus->name }}</span>
                                    </div>
                                    
                                    @if($task->description)
                                        <p class="text-gray-600 text-sm mt-1">{{ Str::limit($task->description, 100) }}</p>
                                    @endif
                                    
                                    <div class="flex items-center space-x-4 mt-2 text-xs text-gray-500">
                                        <span>{{ $task->created_at->format('d.m.Y H:i') }}</span>
                                        @if($task->assignedUser)
                                            <span>Atanan: {{ $task->assignedUser->name }}</span>
                                        @endif
                                    </div>
                                </div>
                                
                                <button wire:click="pushTaskToGitHub({{ $task->id }})" 
                                        class="px-3 py-1 text-sm bg-green-500 text-white rounded hover:bg-green-600">
                                    GitHub'a Gönder
                                </button>
                            </div>
                        @endforeach
                    </div>

                    <!-- Pagination -->
                    @if($localTasks->hasPages())
                        <div class="mt-4">
                            {{ $localTasks->links() }}
                        </div>
                    @endif
                </div>
            </div>
        @endif

        <!-- Last Sync Result -->
        @if($lastSyncResult)
            <div class="bg-white overflow-hidden shadow-sm sm:rounded-lg mt-6">
                <div class="p-6">
                    <h3 class="text-lg font-semibold mb-4">Son Senkronizasyon Sonucu</h3>
                    
                    <div class="grid grid-cols-2 md:grid-cols-4 gap-4 mb-4">
                        <div class="bg-green-50 p-3 rounded text-center">
                            <div class="text-lg font-bold text-green-600">{{ $lastSyncResult['imported_count'] ?? 0 }}</div>
                            <div class="text-sm text-green-700">Import Edildi</div>
                        </div>
                        <div class="bg-blue-50 p-3 rounded text-center">
                            <div class="text-lg font-bold text-blue-600">{{ $lastSyncResult['updated_count'] ?? 0 }}</div>
                            <div class="text-sm text-blue-700">Güncellendi</div>
                        </div>
                        <div class="bg-yellow-50 p-3 rounded text-center">
                            <div class="text-lg font-bold text-yellow-600">{{ $lastSyncResult['skipped_count'] ?? 0 }}</div>
                            <div class="text-sm text-yellow-700">Atlandı</div>
                        </div>
                        <div class="bg-gray-50 p-3 rounded text-center">
                            <div class="text-lg font-bold text-gray-600">{{ $lastSyncResult['total_processed'] ?? 0 }}</div>
                            <div class="text-sm text-gray-700">Toplam İşlenen</div>
                        </div>
                    </div>

                    @if(!empty($lastSyncResult['errors']))
                        <div class="bg-red-50 border border-red-200 rounded p-4">
                            <h4 class="font-semibold text-red-800 mb-2">Hatalar:</h4>
                            <ul class="list-disc list-inside text-sm text-red-700">
                                @foreach($lastSyncResult['errors'] as $error)
                                    <li>{{ $error }}</li>
                                @endforeach
                            </ul>
                        </div>
                    @endif
                </div>
            </div>
        @endif

        <!-- No Repositories Message -->
        @if($repositories->count() === 0)
            <div class="bg-white overflow-hidden shadow-sm sm:rounded-lg">
                <div class="p-6 text-center">
                    <svg class="mx-auto h-12 w-12 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"></path>
                    </svg>
                    <h3 class="mt-2 text-sm font-medium text-gray-900">Henüz GitHub repository bağlanmamış</h3>
                    <p class="mt-1 text-sm text-gray-500">
                        Senkronizasyon yapmak için önce GitHub repository'leri bağlamanız gerekiyor.
                    </p>
                    <div class="mt-6">
                        <a href="/projects/{{ $project->id }}/github" 
                           class="inline-flex items-center px-4 py-2 border border-transparent shadow-sm text-sm font-medium rounded-md text-white bg-blue-600 hover:bg-blue-700">
                            Repository Ekle
                        </a>
                    </div>
                </div>
            </div>
        @endif
    </div>
</div>