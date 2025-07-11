<div class="space-y-4">
    <!-- Header -->
    <div class="flex justify-between items-center">
        <h3 class="text-lg font-semibold text-gray-900">Proje Etiketleri</h3>
        @can('create tasks')
            <button wire:click="toggleCreateForm" 
                    class="px-3 py-1 text-sm bg-blue-500 text-white rounded hover:bg-blue-600 transition-colors">
                {{ $showCreateForm ? 'İptal' : '+ Etiket Ekle' }}
            </button>
        @endcan
    </div>

    <!-- Flash Messages -->
    @if (session()->has('success'))
        <div class="p-3 bg-green-100 border border-green-200 text-green-700 rounded-md text-sm">
            {{ session('success') }}
        </div>
    @endif

    @if (session()->has('error'))
        <div class="p-3 bg-red-100 border border-red-200 text-red-700 rounded-md text-sm">
            {{ session('error') }}
        </div>
    @endif

    <!-- Create Tag Form -->
    @if($showCreateForm)
        <div class="bg-gray-50 rounded-lg p-4 border">
            <form wire:submit="createTag" class="space-y-4">
                <div>
                    <label for="name" class="block text-sm font-medium text-gray-700 mb-1">
                        Etiket Adı
                    </label>
                    <input type="text" id="name" wire:model="name" 
                           class="w-full px-3 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-2 focus:ring-blue-500"
                           placeholder="Frontend, Backend, Bug vb.">
                    @error('name') <span class="text-red-500 text-sm">{{ $message }}</span> @enderror
                </div>

                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-2">
                        Renk Seçin
                    </label>
                    <div class="flex flex-wrap gap-2 mb-2">
                        @foreach($defaultColors as $defaultColor)
                            <button type="button" 
                                    wire:click="setColor('{{ $defaultColor }}')"
                                    class="w-8 h-8 rounded-full border-2 {{ $color === $defaultColor ? 'border-gray-400' : 'border-gray-200' }} hover:border-gray-400 transition-colors"
                                    style="background-color: {{ $defaultColor }}">
                            </button>
                        @endforeach
                    </div>
                    <div class="flex items-center space-x-2">
                        <span class="text-sm text-gray-600">Özel renk:</span>
                        <input type="color" wire:model="color" 
                               class="w-10 h-8 border border-gray-300 rounded cursor-pointer">
                    </div>
                    @error('color') <span class="text-red-500 text-sm">{{ $message }}</span> @enderror
                </div>

                <div class="flex justify-end space-x-2">
                    <button type="button" wire:click="toggleCreateForm"
                            class="px-3 py-2 text-sm bg-gray-500 text-white rounded hover:bg-gray-600 transition-colors">
                        İptal
                    </button>
                    <button type="submit" 
                            class="px-3 py-2 text-sm bg-blue-500 text-white rounded hover:bg-blue-600 transition-colors">
                        Etiket Oluştur
                    </button>
                </div>
            </form>
        </div>
    @endif

    <!-- Existing Tags -->
    <div class="space-y-2">
        @if($project->tags->count() > 0)
            <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-2">
                @foreach($project->tags as $tag)
                    <div class="flex items-center justify-between p-2 border border-gray-200 rounded-md bg-white">
                        <div class="flex items-center space-x-2">
                            <span class="w-4 h-4 rounded-full" style="background-color: {{ $tag->color }}"></span>
                            <span class="text-sm font-medium text-gray-900">{{ $tag->name }}</span>
                            <span class="text-xs text-gray-500">({{ $tag->tasks->count() }})</span>
                        </div>
                        @can('delete tasks')
                            <button wire:click="deleteTag({{ $tag->id }})"
                                    wire:confirm="Bu etiketi silmek istediğinizden emin misiniz? Bu işlem etiketi tüm görevlerden kaldıracaktır."
                                    class="text-red-500 hover:text-red-700 transition-colors">
                                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16"></path>
                                </svg>
                            </button>
                        @endcan
                    </div>
                @endforeach
            </div>
        @else
            <div class="text-center py-8 text-gray-500">
                <svg class="w-12 h-12 mx-auto mb-4 text-gray-300" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M7 7h.01M7 3h5c.512 0 1.024.195 1.414.586l7 7a2 2 0 010 2.828l-7 7a2 2 0 01-2.828 0l-7-7A1.994 1.994 0 013 12V7a4 4 0 014-4z"></path>
                </svg>
                <p>Henüz etiket yok. İlk etiketi oluşturun!</p>
            </div>
        @endif
    </div>
</div>
