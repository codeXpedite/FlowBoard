<div class="py-6">
    <div class="max-w-7xl mx-auto sm:px-6 lg:px-8">
        <div class="bg-white overflow-hidden shadow-sm sm:rounded-lg">
            <div class="p-6 text-gray-900">
                <div class="flex justify-between items-center mb-6">
                    <h2 class="text-2xl font-bold">Dashboard</h2>
                    @can('create projects')
                        <button wire:click="toggleCreateForm" 
                                class="px-4 py-2 bg-green-500 text-white rounded hover:bg-green-600 transition-colors">
                            {{ $showCreateForm ? 'İptal' : '+ Yeni Proje' }}
                        </button>
                    @endcan
                </div>

                <!-- Dashboard Statistics -->
                <div class="grid grid-cols-2 md:grid-cols-3 lg:grid-cols-6 gap-4 mb-8">
                    <div class="bg-blue-50 rounded-lg p-4 border border-blue-200">
                        <div class="text-2xl font-bold text-blue-600">{{ $stats['total_projects'] }}</div>
                        <div class="text-sm text-blue-600">Toplam Proje</div>
                    </div>
                    <div class="bg-green-50 rounded-lg p-4 border border-green-200">
                        <div class="text-2xl font-bold text-green-600">{{ $stats['active_projects'] }}</div>
                        <div class="text-sm text-green-600">Aktif Proje</div>
                    </div>
                    <div class="bg-purple-50 rounded-lg p-4 border border-purple-200">
                        <div class="text-2xl font-bold text-purple-600">{{ $stats['total_tasks'] }}</div>
                        <div class="text-sm text-purple-600">Toplam Görev</div>
                    </div>
                    <div class="bg-emerald-50 rounded-lg p-4 border border-emerald-200">
                        <div class="text-2xl font-bold text-emerald-600">{{ $stats['completed_tasks'] }}</div>
                        <div class="text-sm text-emerald-600">Tamamlanan</div>
                    </div>
                    <div class="bg-red-50 rounded-lg p-4 border border-red-200">
                        <div class="text-2xl font-bold text-red-600">{{ $stats['overdue_tasks'] }}</div>
                        <div class="text-sm text-red-600">Geciken</div>
                    </div>
                    <div class="bg-yellow-50 rounded-lg p-4 border border-yellow-200">
                        <div class="text-2xl font-bold text-yellow-600">{{ $stats['completion_rate'] }}%</div>
                        <div class="text-sm text-yellow-600">Tamamlanma</div>
                    </div>
                </div>

                @if($showCreateForm)
                    <div class="mb-6 p-4 bg-gray-50 rounded-lg border">
                        <h3 class="text-lg font-semibold mb-4">Yeni Proje Oluştur</h3>
                        <form wire:submit="createProject">
                            <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                                <div>
                                    <label for="name" class="block text-sm font-medium text-gray-700 mb-1">
                                        Proje Adı *
                                    </label>
                                    <input type="text" id="name" wire:model="name"
                                           class="w-full px-3 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-2 focus:ring-blue-500"
                                           placeholder="Proje adını girin">
                                    @error('name') <span class="text-red-500 text-sm">{{ $message }}</span> @enderror
                                </div>
                                
                                <div>
                                    <label for="color" class="block text-sm font-medium text-gray-700 mb-1">
                                        Renk
                                    </label>
                                    <input type="color" id="color" wire:model="color"
                                           class="w-20 h-10 border border-gray-300 rounded cursor-pointer">
                                    @error('color') <span class="text-red-500 text-sm">{{ $message }}</span> @enderror
                                </div>
                            </div>
                            
                            <div class="mt-4">
                                <label for="description" class="block text-sm font-medium text-gray-700 mb-1">
                                    Açıklama
                                </label>
                                <textarea id="description" wire:model="description" rows="3"
                                          class="w-full px-3 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-2 focus:ring-blue-500"
                                          placeholder="Proje açıklaması (opsiyonel)"></textarea>
                                @error('description') <span class="text-red-500 text-sm">{{ $message }}</span> @enderror
                            </div>
                            
                            <div class="mt-4 flex space-x-3">
                                <button type="submit" 
                                        class="px-4 py-2 bg-blue-500 text-white rounded hover:bg-blue-600 transition-colors">
                                    Proje Oluştur
                                </button>
                                <button type="button" wire:click="toggleCreateForm"
                                        class="px-4 py-2 bg-gray-500 text-white rounded hover:bg-gray-600 transition-colors">
                                    İptal
                                </button>
                            </div>
                        </form>
                    </div>
                @endif
                
                @if($projects->count() > 0)
                    <div class="mb-4">
                        <h3 class="text-lg font-semibold text-gray-800">Projelerim</h3>
                    </div>
                    <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-4">
                        @foreach($projects as $project)
                            <div class="bg-gray-50 rounded-lg p-4 border border-gray-200 hover:shadow-md transition-shadow">
                                <div class="flex items-center mb-2">
                                    <div class="w-4 h-4 rounded-full mr-2" style="background-color: {{ $project->color }}"></div>
                                    <h3 class="font-semibold text-lg">{{ $project->name }}</h3>
                                </div>
                                
                                @if($project->description)
                                    <p class="text-gray-600 mb-3">{{ $project->description }}</p>
                                @endif
                                
                                <div class="flex justify-between items-center mb-3">
                                    <span class="px-2 py-1 rounded text-xs font-medium
                                        @if($project->status === 'active') bg-green-100 text-green-800
                                        @elseif($project->status === 'archived') bg-gray-100 text-gray-800
                                        @else bg-blue-100 text-blue-800
                                        @endif">
                                        {{ ucfirst($project->status) }}
                                    </span>
                                    
                                    <span class="text-sm text-gray-500">{{ $project->created_at->format('d.m.Y') }}</span>
                                </div>

                                <div class="text-xs text-gray-500 mb-3">
                                    Toplam: {{ $project->tasks_count }} | 
                                    Aktif: {{ $project->active_tasks_count }} | 
                                    Tamamlanan: {{ $project->completed_tasks_count }}
                                </div>
                                
                                <div class="flex space-x-2">
                                    <a href="/projects/{{ $project->id }}" 
                                       class="px-3 py-1 bg-blue-500 text-white rounded text-sm hover:bg-blue-600 transition-colors">
                                        Aç
                                    </a>
                                    <button wire:click="deleteProject({{ $project->id }})"
                                            wire:confirm="Bu projeyi silmek istediğinizden emin misiniz?"
                                            class="px-3 py-1 bg-red-500 text-white rounded text-sm hover:bg-red-600 transition-colors">
                                        Sil
                                    </button>
                                </div>
                            </div>
                        @endforeach
                    </div>
                @else
                    <div class="text-center py-8">
                        <p class="text-gray-500 mb-4">Henüz hiç projeniz yok.</p>
                        <button wire:click="toggleCreateForm" 
                                class="px-4 py-2 bg-blue-500 text-white rounded hover:bg-blue-600 transition-colors">
                            İlk Projenizi Oluşturun
                        </button>
                    </div>
                @endif
            </div>
        </div>
    </div>
</div>
