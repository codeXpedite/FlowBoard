<div class="py-6">
    <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
        <div class="bg-white overflow-hidden shadow-sm sm:rounded-lg">
            <div class="p-6 text-gray-900">
                <div class="flex justify-between items-center mb-6">
                    <h2 class="text-2xl font-bold">Proje Ayarları</h2>
                    <a href="/projects/{{ $project->id }}" 
                       class="bg-gray-500 hover:bg-gray-700 text-white font-bold py-2 px-4 rounded">
                        Geri Dön
                    </a>
                </div>

                <!-- Success Message -->
                @if (session()->has('message'))
                    <div class="bg-green-100 border border-green-400 text-green-700 px-4 py-3 rounded mb-4">
                        {{ session('message') }}
                    </div>
                @endif

                <!-- Error Message -->
                @if (session()->has('error'))
                    <div class="bg-red-100 border border-red-400 text-red-700 px-4 py-3 rounded mb-4">
                        {{ session('error') }}
                    </div>
                @endif

                <form wire:submit="updateProject">
                    <!-- Basic Settings -->
                    <div class="mb-8">
                        <h3 class="text-lg font-semibold mb-4">Temel Bilgiler</h3>
                        
                        <div class="mb-4">
                            <label for="name" class="block text-sm font-medium text-gray-700 mb-2">
                                Proje Adı
                            </label>
                            <input type="text" 
                                   id="name" 
                                   wire:model="name" 
                                   class="w-full border border-gray-300 rounded-md shadow-sm py-2 px-3 focus:outline-none focus:ring-blue-500 focus:border-blue-500"
                                   required>
                            @error('name') 
                                <span class="text-red-500 text-sm">{{ $message }}</span> 
                            @enderror
                        </div>

                        <div class="mb-4">
                            <label for="description" class="block text-sm font-medium text-gray-700 mb-2">
                                Açıklama
                            </label>
                            <textarea id="description" 
                                      wire:model="description" 
                                      rows="3"
                                      class="w-full border border-gray-300 rounded-md shadow-sm py-2 px-3 focus:outline-none focus:ring-blue-500 focus:border-blue-500"></textarea>
                            @error('description') 
                                <span class="text-red-500 text-sm">{{ $message }}</span> 
                            @enderror
                        </div>
                    </div>

                    <!-- Notification Settings -->
                    <div class="mb-8">
                        <h3 class="text-lg font-semibold mb-4">Bildirim Ayarları</h3>
                        
                        <div class="space-y-4">
                            <div class="flex items-center">
                                <input id="email-notifications" 
                                       type="checkbox" 
                                       wire:model="emailNotifications"
                                       class="h-4 w-4 text-blue-600 focus:ring-blue-500 border-gray-300 rounded">
                                <label for="email-notifications" class="ml-2 block text-sm text-gray-900">
                                    Email bildirimleri
                                </label>
                            </div>

                            <div class="ml-6 space-y-3" x-show="$wire.emailNotifications">
                                <div class="flex items-center">
                                    <input id="task-assignments" 
                                           type="checkbox" 
                                           wire:model="taskAssignments"
                                           class="h-4 w-4 text-blue-600 focus:ring-blue-500 border-gray-300 rounded">
                                    <label for="task-assignments" class="ml-2 block text-sm text-gray-700">
                                        Görev atamaları
                                    </label>
                                </div>

                                <div class="flex items-center">
                                    <input id="status-updates" 
                                           type="checkbox" 
                                           wire:model="statusUpdates"
                                           class="h-4 w-4 text-blue-600 focus:ring-blue-500 border-gray-300 rounded">
                                    <label for="status-updates" class="ml-2 block text-sm text-gray-700">
                                        Durum değişiklikleri
                                    </label>
                                </div>

                                <div class="flex items-center">
                                    <input id="comments" 
                                           type="checkbox" 
                                           wire:model="comments"
                                           class="h-4 w-4 text-blue-600 focus:ring-blue-500 border-gray-300 rounded">
                                    <label for="comments" class="ml-2 block text-sm text-gray-700">
                                        Yeni yorumlar
                                    </label>
                                </div>

                                <div class="flex items-center">
                                    <input id="project-updates" 
                                           type="checkbox" 
                                           wire:model="projectUpdates"
                                           class="h-4 w-4 text-blue-600 focus:ring-blue-500 border-gray-300 rounded">
                                    <label for="project-updates" class="ml-2 block text-sm text-gray-700">
                                        Proje güncellemeleri
                                    </label>
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- Save Button -->
                    <div class="flex justify-end mb-8">
                        <button type="submit" 
                                class="bg-blue-600 hover:bg-blue-700 text-white font-bold py-2 px-4 rounded">
                            Ayarları Kaydet
                        </button>
                    </div>
                </form>

                <!-- Danger Zone -->
                <div class="border-t pt-8">
                    <h3 class="text-lg font-semibold text-red-600 mb-4">Tehlikeli İşlemler</h3>
                    
                    <div class="space-y-4">
                        <!-- Archive/Unarchive Project -->
                        @if(!($settings['archived'] ?? false))
                            <div class="flex items-center justify-between p-4 border border-yellow-300 rounded-lg bg-yellow-50">
                                <div>
                                    <h4 class="font-medium text-yellow-800">Projeyi Arşivle</h4>
                                    <p class="text-sm text-yellow-700">Proje arşivlendiğinde yeni görevler eklenemez.</p>
                                </div>
                                <button wire:click="archiveProject" 
                                        onclick="return confirm('Bu projeyi arşivlemek istediğinizden emin misiniz?')"
                                        class="bg-yellow-600 hover:bg-yellow-700 text-white font-bold py-2 px-4 rounded">
                                    Arşivle
                                </button>
                            </div>
                        @else
                            <div class="flex items-center justify-between p-4 border border-green-300 rounded-lg bg-green-50">
                                <div>
                                    <h4 class="font-medium text-green-800">Arşivden Çıkar</h4>
                                    <p class="text-sm text-green-700">Projeyi tekrar aktif hale getir.</p>
                                </div>
                                <button wire:click="unarchiveProject"
                                        class="bg-green-600 hover:bg-green-700 text-white font-bold py-2 px-4 rounded">
                                    Arşivden Çıkar
                                </button>
                            </div>
                        @endif

                        <!-- Delete Project -->
                        <div class="flex items-center justify-between p-4 border border-red-300 rounded-lg bg-red-50">
                            <div>
                                <h4 class="font-medium text-red-800">Projeyi Sil</h4>
                                <p class="text-sm text-red-700">Bu işlem geri alınamaz. Tüm görevler ve veriler silinir.</p>
                            </div>
                            <button wire:click="deleteProject" 
                                    onclick="return confirm('Bu projeyi silmek istediğinizden emin misiniz? Bu işlem geri alınamaz!')"
                                    class="bg-red-600 hover:bg-red-700 text-white font-bold py-2 px-4 rounded">
                                Projeyi Sil
                            </button>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<script>
    document.addEventListener('alpine:init', () => {
        Alpine.data('projectSettings', () => ({
            // Add any Alpine.js logic here if needed
        }))
    })
</script>