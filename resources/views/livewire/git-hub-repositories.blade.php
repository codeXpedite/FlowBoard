<div class="py-6">
    <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
        <div class="bg-white overflow-hidden shadow-sm sm:rounded-lg">
            <div class="p-6 text-gray-900">
                <div class="flex justify-between items-center mb-6">
                    <div>
                        <h2 class="text-2xl font-bold">GitHub Repositories</h2>
                        <p class="text-gray-600">{{ $project->name }} projesi için GitHub repository'leri yönetin</p>
                    </div>
                    <div class="flex space-x-3">
                        <button wire:click="openAddForm" 
                                class="bg-blue-600 hover:bg-blue-700 text-white font-bold py-2 px-4 rounded">
                            + Repository Ekle
                        </button>
                        <a href="/projects/{{ $project->id }}/webhooks" 
                           class="bg-purple-500 hover:bg-purple-600 text-white font-bold py-2 px-4 rounded">
                            Webhook Dashboard
                        </a>
                        <a href="/projects/{{ $project->id }}" 
                           class="bg-gray-500 hover:bg-gray-600 text-white font-bold py-2 px-4 rounded">
                            Geri Dön
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

                <!-- Add Repository Form -->
                @if($showAddForm)
                    <div class="bg-gray-50 rounded-lg p-6 mb-6">
                        <div class="flex justify-between items-center mb-4">
                            <h3 class="text-lg font-semibold">Repository Ekle</h3>
                            <button wire:click="closeAddForm" class="text-gray-500 hover:text-gray-700">
                                <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"></path>
                                </svg>
                            </button>
                        </div>

                        <form wire:submit="addRepository">
                            <!-- GitHub Token -->
                            <div class="mb-4">
                                <label for="githubToken" class="block text-sm font-medium text-gray-700 mb-2">
                                    GitHub Personal Access Token (isteğe bağlı)
                                </label>
                                <input type="password" 
                                       id="githubToken" 
                                       wire:model="githubToken" 
                                       placeholder="ghp_xxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxx"
                                       class="w-full border border-gray-300 rounded-md shadow-sm py-2 px-3 focus:outline-none focus:ring-blue-500 focus:border-blue-500">
                                <p class="text-xs text-gray-500 mt-1">Token ile repository arama ve otomatik webhook kurulumu yapılabilir.</p>
                            </div>

                            <!-- Repository Search -->
                            @if($githubToken)
                                <div class="mb-4">
                                    <label class="block text-sm font-medium text-gray-700 mb-2">
                                        Repository Ara
                                    </label>
                                    <div class="flex space-x-2">
                                        <input type="text" 
                                               wire:model="searchQuery" 
                                               placeholder="Repository adı ara..."
                                               class="flex-1 border border-gray-300 rounded-md shadow-sm py-2 px-3 focus:outline-none focus:ring-blue-500 focus:border-blue-500">
                                        <button type="button" 
                                                wire:click="searchRepositories" 
                                                wire:loading.attr="disabled"
                                                class="bg-gray-600 hover:bg-gray-700 text-white font-bold py-2 px-4 rounded disabled:opacity-50">
                                            <span wire:loading.remove>Ara</span>
                                            <span wire:loading>Arıyor...</span>
                                        </button>
                                    </div>
                                </div>

                                <!-- Available Repositories -->
                                @if(!empty($availableRepositories))
                                    <div class="mb-4">
                                        <label class="block text-sm font-medium text-gray-700 mb-2">
                                            Mevcut Repository'ler
                                        </label>
                                        <div class="max-h-60 overflow-y-auto border border-gray-200 rounded-md">
                                            @foreach($availableRepositories as $repo)
                                                <div class="p-3 border-b border-gray-200 hover:bg-gray-50 cursor-pointer"
                                                     wire:click="selectRepository({{ json_encode($repo) }})">
                                                    <div class="flex items-center justify-between">
                                                        <div>
                                                            <div class="font-medium text-gray-900">{{ $repo['name'] }}</div>
                                                            <div class="text-sm text-gray-500">{{ $repo['full_name'] }}</div>
                                                            @if($repo['description'])
                                                                <div class="text-sm text-gray-600">{{ Str::limit($repo['description'], 100) }}</div>
                                                            @endif
                                                        </div>
                                                        <div class="flex items-center space-x-2">
                                                            @if($repo['private'])
                                                                <span class="bg-yellow-100 text-yellow-800 text-xs px-2 py-1 rounded">Private</span>
                                                            @else
                                                                <span class="bg-green-100 text-green-800 text-xs px-2 py-1 rounded">Public</span>
                                                            @endif
                                                        </div>
                                                    </div>
                                                </div>
                                            @endforeach
                                        </div>
                                    </div>
                                @endif
                            @endif

                            <!-- Repository URL -->
                            <div class="mb-4">
                                <label for="repositoryUrl" class="block text-sm font-medium text-gray-700 mb-2">
                                    Repository URL
                                </label>
                                <input type="url" 
                                       id="repositoryUrl" 
                                       wire:model="repositoryUrl" 
                                       placeholder="https://github.com/kullanici/repository"
                                       class="w-full border border-gray-300 rounded-md shadow-sm py-2 px-3 focus:outline-none focus:ring-blue-500 focus:border-blue-500"
                                       required>
                                @error('repositoryUrl') 
                                    <span class="text-red-500 text-sm">{{ $message }}</span> 
                                @enderror
                            </div>

                            <!-- Submit Button -->
                            <div class="flex justify-end space-x-3">
                                <button type="button" 
                                        wire:click="closeAddForm"
                                        class="bg-gray-500 hover:bg-gray-600 text-white font-bold py-2 px-4 rounded">
                                    İptal
                                </button>
                                <button type="submit" 
                                        wire:loading.attr="disabled"
                                        class="bg-blue-600 hover:bg-blue-700 text-white font-bold py-2 px-4 rounded disabled:opacity-50">
                                    <span wire:loading.remove>Repository Ekle</span>
                                    <span wire:loading>Ekleniyor...</span>
                                </button>
                            </div>
                        </form>
                    </div>
                @endif

                <!-- Repositories List -->
                @if($repositories->count() > 0)
                    <div class="grid gap-4">
                        @foreach($repositories as $repository)
                            <div class="border border-gray-200 rounded-lg p-4 {{ !$repository->active ? 'bg-gray-50 opacity-75' : 'bg-white' }}">
                                <div class="flex items-center justify-between">
                                    <div class="flex-1">
                                        <div class="flex items-center space-x-3">
                                            <h3 class="text-lg font-semibold">
                                                <a href="{{ $repository->html_url }}" 
                                                   target="_blank" 
                                                   class="text-blue-600 hover:text-blue-800">
                                                    {{ $repository->full_name }}
                                                </a>
                                            </h3>
                                            @if($repository->private)
                                                <span class="bg-yellow-100 text-yellow-800 text-xs px-2 py-1 rounded">Private</span>
                                            @else
                                                <span class="bg-green-100 text-green-800 text-xs px-2 py-1 rounded">Public</span>
                                            @endif
                                            @if(!$repository->active)
                                                <span class="bg-gray-100 text-gray-800 text-xs px-2 py-1 rounded">Pasif</span>
                                            @endif
                                        </div>
                                        
                                        @if($repository->description)
                                            <p class="text-gray-600 mt-1">{{ $repository->description }}</p>
                                        @endif
                                        
                                        <div class="flex items-center space-x-4 mt-2 text-sm text-gray-500">
                                            <span>Ana dal: {{ $repository->default_branch }}</span>
                                            @if($repository->webhook_id)
                                                <span class="flex items-center">
                                                    <svg class="w-4 h-4 mr-1 text-green-500" fill="currentColor" viewBox="0 0 20 20">
                                                        <path fill-rule="evenodd" d="M16.707 5.293a1 1 0 010 1.414l-8 8a1 1 0 01-1.414 0l-4-4a1 1 0 011.414-1.414L8 12.586l7.293-7.293a1 1 0 011.414 0z" clip-rule="evenodd"></path>
                                                    </svg>
                                                    Webhook aktif
                                                </span>
                                            @else
                                                <span class="flex items-center text-yellow-600">
                                                    <svg class="w-4 h-4 mr-1" fill="currentColor" viewBox="0 0 20 20">
                                                        <path fill-rule="evenodd" d="M8.257 3.099c.765-1.36 2.722-1.36 3.486 0l5.58 9.92c.75 1.334-.213 2.98-1.742 2.98H4.42c-1.53 0-2.493-1.646-1.743-2.98l5.58-9.92zM11 13a1 1 0 11-2 0 1 1 0 012 0zm-1-8a1 1 0 00-1 1v3a1 1 0 002 0V6a1 1 0 00-1-1z" clip-rule="evenodd"></path>
                                                    </svg>
                                                    Webhook kurulmamış
                                                </span>
                                            @endif
                                            @if($repository->last_sync_at)
                                                <span>Son sync: {{ $repository->last_sync_at->diffForHumans() }}</span>
                                            @endif
                                        </div>

                                        <!-- Webhook URL -->
                                        @if($repository->webhook_id)
                                            <div class="mt-2">
                                                <label class="text-xs font-medium text-gray-700">Webhook URL:</label>
                                                <div class="flex items-center space-x-2 mt-1">
                                                    <code class="bg-gray-100 px-2 py-1 rounded text-xs flex-1">{{ $repository->webhook_url }}</code>
                                                    <button onclick="navigator.clipboard.writeText('{{ $repository->webhook_url }}')" 
                                                            class="text-blue-600 hover:text-blue-800 text-xs">
                                                        Kopyala
                                                    </button>
                                                </div>
                                            </div>
                                        @endif
                                    </div>

                                    <!-- Actions -->
                                    <div class="flex items-center space-x-2 ml-4">
                                        <button wire:click="toggleRepository({{ $repository->id }})"
                                                class="px-3 py-1 text-sm {{ $repository->active ? 'bg-yellow-500 hover:bg-yellow-600' : 'bg-green-500 hover:bg-green-600' }} text-white rounded">
                                            {{ $repository->active ? 'Pasif Yap' : 'Aktif Yap' }}
                                        </button>
                                        
                                        <button wire:click="syncRepository({{ $repository->id }})"
                                                class="px-3 py-1 text-sm bg-blue-500 hover:bg-blue-600 text-white rounded">
                                            Sync
                                        </button>
                                        
                                        <button wire:click="removeRepository({{ $repository->id }})"
                                                onclick="return confirm('Bu repository\'yi kaldırmak istediğinizden emin misiniz?')"
                                                class="px-3 py-1 text-sm bg-red-500 hover:bg-red-600 text-white rounded">
                                            Kaldır
                                        </button>
                                    </div>
                                </div>

                                <!-- Repository Stats -->
                                <div class="mt-4 grid grid-cols-3 gap-4 text-center border-t pt-4">
                                    <div>
                                        <div class="text-lg font-semibold">{{ $repository->tasks()->count() }}</div>
                                        <div class="text-sm text-gray-500">Toplam Görev</div>
                                    </div>
                                    <div>
                                        <div class="text-lg font-semibold">{{ $repository->webhooks()->count() }}</div>
                                        <div class="text-sm text-gray-500">Webhook Olayı</div>
                                    </div>
                                    <div>
                                        <div class="text-lg font-semibold">{{ $repository->webhooks()->processed()->count() }}</div>
                                        <div class="text-sm text-gray-500">İşlenen Olay</div>
                                    </div>
                                </div>
                            </div>
                        @endforeach
                    </div>
                @else
                    <div class="text-center py-12">
                        <svg class="mx-auto h-12 w-12 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16"></path>
                        </svg>
                        <h3 class="mt-2 text-sm font-medium text-gray-900">Henüz repository eklenmemiş</h3>
                        <p class="mt-1 text-sm text-gray-500">
                            GitHub repository ekleyerek otomatik görev yönetimini başlatın.
                        </p>
                    </div>
                @endif
            </div>
        </div>
    </div>
</div>