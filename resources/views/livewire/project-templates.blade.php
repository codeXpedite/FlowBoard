<div class="py-6">
    <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
        <!-- Header -->
        <div class="bg-white overflow-hidden shadow-sm sm:rounded-lg mb-6">
            <div class="p-6">
                <div class="flex justify-between items-center mb-4">
                    <div>
                        <h2 class="text-2xl font-bold text-gray-900 flex items-center">
                            <svg class="w-6 h-6 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 11H5m14 0a2 2 0 012 2v6a2 2 0 01-2 2H5a2 2 0 01-2-2v-6a2 2 0 012-2m14 0V9a2 2 0 00-2-2M5 11V9a2 2 0 012-2m0 0V5a2 2 0 012-2h6a2 2 0 012 2v2M7 7h10"></path>
                            </svg>
                            Proje Şablonları
                        </h2>
                        <p class="text-gray-600">Hazır şablonlardan projelerinizi hızlıca başlatın</p>
                    </div>
                    <div class="flex space-x-3">
                        <button wire:click="openCreateModal" class="px-4 py-2 bg-green-600 text-white rounded hover:bg-green-700 transition-colors">
                            Şablon Oluştur
                        </button>
                        <a href="/dashboard" class="px-4 py-2 bg-gray-500 text-white rounded hover:bg-gray-600 transition-colors">
                            Geri Dön
                        </a>
                    </div>
                </div>

                <!-- Search and Filters -->
                <div class="flex flex-wrap items-center gap-4 mb-6">
                    <!-- Search -->
                    <div class="flex-1 min-w-64">
                        <input wire:model.live="search" 
                               type="text" 
                               placeholder="Şablon ara..." 
                               class="w-full px-4 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-2 focus:ring-blue-500">
                    </div>

                    <!-- Category Filter -->
                    <div>
                        <select wire:model.live="selectedCategory" 
                                class="px-4 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-2 focus:ring-blue-500">
                            <option value="">Tüm Kategoriler</option>
                            @foreach($categories as $key => $name)
                                <option value="{{ $key }}">{{ $name }}</option>
                            @endforeach
                        </select>
                    </div>

                    <!-- Clear Filters -->
                    @if($search || $selectedCategory)
                        <button wire:click="clearFilters" 
                                class="px-4 py-2 bg-gray-100 text-gray-700 rounded hover:bg-gray-200 transition-colors">
                            Temizle
                        </button>
                    @endif
                </div>

                <!-- Success Message -->
                @if (session()->has('message'))
                    <div class="bg-green-100 border border-green-400 text-green-700 px-4 py-3 rounded mb-4">
                        {{ session('message') }}
                    </div>
                @endif
            </div>
        </div>

        <!-- Popular Templates -->
        @if(!$search && !$selectedCategory && $popularTemplates->count() > 0)
            <div class="bg-white overflow-hidden shadow-sm sm:rounded-lg mb-6">
                <div class="p-6">
                    <h3 class="text-lg font-semibold text-gray-900 mb-4">Popüler Şablonlar</h3>
                    <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-4">
                        @foreach($popularTemplates as $template)
                            <div class="border border-gray-200 rounded-lg p-4 hover:shadow-md transition-shadow cursor-pointer"
                                 wire:click="selectTemplate({{ $template->id }})">
                                <div class="flex items-center justify-between mb-3">
                                    <div class="flex items-center space-x-2">
                                        <div class="w-4 h-4 rounded-full" style="background-color: {{ $template->color }}"></div>
                                        <span class="text-xs bg-gray-100 text-gray-700 px-2 py-1 rounded">{{ $template->getCategoryDisplayName() }}</span>
                                    </div>
                                    <span class="text-xs text-gray-500">{{ $template->usage_count }} kullanım</span>
                                </div>
                                <h4 class="font-medium text-gray-900 mb-2">{{ $template->name }}</h4>
                                <p class="text-sm text-gray-600 mb-3 line-clamp-2">{{ $template->description }}</p>
                                <div class="flex justify-between items-center">
                                    <span class="text-xs text-gray-500">
                                        @if($template->creator)
                                            {{ $template->creator->name }}
                                        @else
                                            Sistem
                                        @endif
                                    </span>
                                    <button wire:click.stop="createProjectFromTemplate({{ $template->id }})" 
                                            class="text-blue-600 hover:text-blue-800 text-sm font-medium">
                                        Kullan
                                    </button>
                                </div>
                            </div>
                        @endforeach
                    </div>
                </div>
            </div>
        @endif

        <!-- All Templates -->
        <div class="bg-white overflow-hidden shadow-sm sm:rounded-lg">
            <div class="p-6">
                <h3 class="text-lg font-semibold text-gray-900 mb-4">
                    @if($search)
                        "{{ $search }}" için arama sonuçları
                    @elseif($selectedCategory)
                        {{ $categories[$selectedCategory] }} Şablonları
                    @else
                        Tüm Şablonlar
                    @endif
                    ({{ $templates->count() }})
                </h3>

                @if($templates->count() > 0)
                    <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 xl:grid-cols-4 gap-4">
                        @foreach($templates as $template)
                            <div class="border border-gray-200 rounded-lg p-4 hover:shadow-md transition-shadow cursor-pointer"
                                 wire:click="selectTemplate({{ $template->id }})">
                                <div class="flex items-center justify-between mb-3">
                                    <div class="flex items-center space-x-2">
                                        <div class="w-3 h-3 rounded-full" style="background-color: {{ $template->color }}"></div>
                                        <span class="text-xs bg-gray-100 text-gray-700 px-2 py-1 rounded">{{ $template->getCategoryDisplayName() }}</span>
                                    </div>
                                    @if($template->usage_count > 0)
                                        <span class="text-xs text-gray-500">{{ $template->usage_count }}</span>
                                    @endif
                                </div>
                                
                                <h4 class="font-medium text-gray-900 mb-2">{{ $template->name }}</h4>
                                <p class="text-sm text-gray-600 mb-3 line-clamp-3">{{ Str::limit($template->description, 100) }}</p>
                                
                                @if($template->tags && count($template->tags) > 0)
                                    <div class="flex flex-wrap gap-1 mb-3">
                                        @foreach(array_slice($template->tags, 0, 3) as $tag)
                                            <span class="text-xs bg-blue-100 text-blue-700 px-2 py-1 rounded">{{ $tag }}</span>
                                        @endforeach
                                    </div>
                                @endif
                                
                                <div class="flex justify-between items-center">
                                    <span class="text-xs text-gray-500">
                                        @if($template->creator)
                                            {{ $template->creator->name }}
                                        @else
                                            Sistem
                                        @endif
                                    </span>
                                    <button wire:click.stop="createProjectFromTemplate({{ $template->id }})" 
                                            class="text-blue-600 hover:text-blue-800 text-sm font-medium">
                                        Kullan
                                    </button>
                                </div>
                            </div>
                        @endforeach
                    </div>
                @else
                    <div class="text-center py-8">
                        <svg class="mx-auto h-12 w-12 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1" d="M19 11H5m14 0a2 2 0 012 2v6a2 2 0 01-2 2H5a2 2 0 01-2-2v-6a2 2 0 012-2m14 0V9a2 2 0 00-2-2M5 11V9a2 2 0 012-2m0 0V5a2 2 0 012-2h6a2 2 0 012 2v2M7 7h10"></path>
                        </svg>
                        <h3 class="mt-2 text-sm font-medium text-gray-900">Şablon bulunamadı</h3>
                        <p class="mt-1 text-sm text-gray-500">
                            @if($search || $selectedCategory)
                                Arama kriterlerinize uygun şablon bulunamadı.
                            @else
                                Henüz hiç şablon bulunmuyor.
                            @endif
                        </p>
                    </div>
                @endif
            </div>
        </div>
    </div>

    <!-- Template Detail Modal -->
    @if($showTemplateDetail && $selectedTemplate)
        <div class="fixed inset-0 bg-gray-600 bg-opacity-50 overflow-y-auto h-full w-full z-50">
            <div class="relative top-20 mx-auto p-5 border w-11/12 md:w-3/4 lg:w-1/2 shadow-lg rounded-md bg-white">
                <div class="flex justify-between items-center mb-4">
                    <h3 class="text-lg font-bold text-gray-900">{{ $selectedTemplate->name }}</h3>
                    <button wire:click="closeTemplateDetail" class="text-gray-400 hover:text-gray-600">
                        <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"></path>
                        </svg>
                    </button>
                </div>

                <div class="space-y-4">
                    <!-- Template Info -->
                    <div class="flex items-center space-x-4">
                        <div class="w-6 h-6 rounded-full" style="background-color: {{ $selectedTemplate->color }}"></div>
                        <span class="bg-gray-100 text-gray-700 px-3 py-1 rounded text-sm">{{ $selectedTemplate->getCategoryDisplayName() }}</span>
                        @if($selectedTemplate->usage_count > 0)
                            <span class="text-sm text-gray-500">{{ $selectedTemplate->usage_count }} kez kullanıldı</span>
                        @endif
                    </div>

                    <p class="text-gray-700">{{ $selectedTemplate->description }}</p>

                    <!-- Tags -->
                    @if($selectedTemplate->tags && count($selectedTemplate->tags) > 0)
                        <div>
                            <h4 class="text-sm font-medium text-gray-900 mb-2">Etiketler</h4>
                            <div class="flex flex-wrap gap-2">
                                @foreach($selectedTemplate->tags as $tag)
                                    <span class="bg-blue-100 text-blue-700 px-2 py-1 rounded text-sm">{{ $tag }}</span>
                                @endforeach
                            </div>
                        </div>
                    @endif

                    <!-- Task Statuses -->
                    @if($selectedTemplate->default_task_statuses)
                        <div>
                            <h4 class="text-sm font-medium text-gray-900 mb-2">Görev Durumları</h4>
                            <div class="grid grid-cols-2 md:grid-cols-3 gap-2">
                                @foreach($selectedTemplate->getDefaultTaskStatusesAsObjects() as $status)
                                    <div class="flex items-center space-x-2 p-2 bg-gray-50 rounded">
                                        <div class="w-3 h-3 rounded-full" style="background-color: {{ $status['color'] }}"></div>
                                        <span class="text-sm">{{ $status['name'] }}</span>
                                    </div>
                                @endforeach
                            </div>
                        </div>
                    @endif

                    <!-- Default Tasks -->
                    @if($selectedTemplate->default_tasks && count($selectedTemplate->default_tasks) > 0)
                        <div>
                            <h4 class="text-sm font-medium text-gray-900 mb-2">Örnek Görevler ({{ count($selectedTemplate->default_tasks) }})</h4>
                            <div class="space-y-2 max-h-40 overflow-y-auto">
                                @foreach($selectedTemplate->getDefaultTasksAsObjects() as $task)
                                    <div class="p-2 bg-gray-50 rounded flex justify-between items-center">
                                        <span class="text-sm">{{ $task['title'] }}</span>
                                        <span class="text-xs px-2 py-1 rounded {{ $task['priority'] === 'high' ? 'bg-red-100 text-red-700' : ($task['priority'] === 'medium' ? 'bg-yellow-100 text-yellow-700' : 'bg-green-100 text-green-700') }}">
                                            {{ ucfirst($task['priority']) }}
                                        </span>
                                    </div>
                                @endforeach
                            </div>
                        </div>
                    @endif

                    <!-- Creator Info -->
                    <div class="text-sm text-gray-500">
                        @if($selectedTemplate->creator)
                            {{ $selectedTemplate->creator->name }} tarafından oluşturuldu
                        @else
                            Sistem şablonu
                        @endif
                        • {{ $selectedTemplate->created_at->diffForHumans() }}
                    </div>
                </div>

                <!-- Actions -->
                <div class="flex justify-end space-x-3 mt-6 pt-4 border-t">
                    <button wire:click="closeTemplateDetail" 
                            class="px-4 py-2 bg-gray-300 text-gray-700 rounded hover:bg-gray-400 transition-colors">
                        Kapat
                    </button>
                    <button wire:click="createProjectFromTemplate({{ $selectedTemplate->id }})" 
                            class="px-4 py-2 bg-blue-600 text-white rounded hover:bg-blue-700 transition-colors">
                        Bu Şablonu Kullan
                    </button>
                </div>
            </div>
        </div>
    @endif

    <!-- Create Template Modal -->
    @if($showCreateModal)
        <div class="fixed inset-0 bg-gray-600 bg-opacity-50 overflow-y-auto h-full w-full z-50">
            <div class="relative top-20 mx-auto p-5 border w-11/12 md:w-2/3 lg:w-1/2 shadow-lg rounded-md bg-white">
                <div class="flex justify-between items-center mb-4">
                    <h3 class="text-lg font-bold text-gray-900">Yeni Şablon Oluştur</h3>
                    <button wire:click="closeCreateModal" class="text-gray-400 hover:text-gray-600">
                        <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"></path>
                        </svg>
                    </button>
                </div>

                <form wire:submit.prevent="createTemplate" class="space-y-4">
                    <!-- Template Name -->
                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-1">Şablon Adı</label>
                        <input wire:model="templateName" 
                               type="text" 
                               class="w-full px-3 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-2 focus:ring-blue-500"
                               placeholder="Şablon adı...">
                        @error('templateName') <span class="text-red-500 text-sm">{{ $message }}</span> @enderror
                    </div>

                    <!-- Template Description -->
                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-1">Açıklama</label>
                        <textarea wire:model="templateDescription" 
                                  rows="3"
                                  class="w-full px-3 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-2 focus:ring-blue-500"
                                  placeholder="Şablon açıklaması..."></textarea>
                        @error('templateDescription') <span class="text-red-500 text-sm">{{ $message }}</span> @enderror
                    </div>

                    <!-- Category and Color -->
                    <div class="grid grid-cols-2 gap-4">
                        <div>
                            <label class="block text-sm font-medium text-gray-700 mb-1">Kategori</label>
                            <select wire:model="templateCategory" 
                                    class="w-full px-3 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-2 focus:ring-blue-500">
                                @foreach($categories as $key => $name)
                                    <option value="{{ $key }}">{{ $name }}</option>
                                @endforeach
                            </select>
                        </div>

                        <div>
                            <label class="block text-sm font-medium text-gray-700 mb-1">Renk</label>
                            <input wire:model="templateColor" 
                                   type="color" 
                                   class="w-full h-10 border border-gray-300 rounded-md">
                        </div>
                    </div>

                    <!-- Public Template -->
                    <div class="flex items-center">
                        <input wire:model="isPublic" 
                               type="checkbox" 
                               class="h-4 w-4 text-blue-600 focus:ring-blue-500 border-gray-300 rounded">
                        <label class="ml-2 text-sm text-gray-700">Bu şablonu herkesle paylaş</label>
                    </div>

                    <!-- Actions -->
                    <div class="flex justify-end space-x-3 pt-4 border-t">
                        <button type="button" 
                                wire:click="closeCreateModal"
                                class="px-4 py-2 bg-gray-300 text-gray-700 rounded hover:bg-gray-400 transition-colors">
                            İptal
                        </button>
                        <button type="submit" 
                                class="px-4 py-2 bg-green-600 text-white rounded hover:bg-green-700 transition-colors">
                            Şablon Oluştur
                        </button>
                    </div>
                </form>
            </div>
        </div>
    @endif

    <style>
        .line-clamp-2 {
            display: -webkit-box;
            -webkit-line-clamp: 2;
            -webkit-box-orient: vertical;
            overflow: hidden;
        }
        .line-clamp-3 {
            display: -webkit-box;
            -webkit-line-clamp: 3;
            -webkit-box-orient: vertical;
            overflow: hidden;
        }
    </style>
</div>