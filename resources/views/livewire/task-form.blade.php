<div>
    @if($showModal)
        <div class="fixed inset-0 bg-gray-600 bg-opacity-50 overflow-y-auto h-full w-full z-50" wire:click="closeModal">
            <div class="relative top-20 mx-auto p-5 border w-11/12 md:w-3/4 lg:w-1/2 shadow-lg rounded-md bg-white" wire:click.stop>
                <div class="mt-3">
                    <div class="flex justify-between items-center mb-4">
                        <h3 class="text-lg font-medium text-gray-900">
                            {{ $isEditing ? 'Görevi Düzenle' : 'Yeni Görev Oluştur' }}
                        </h3>
                        <button wire:click="closeModal" class="text-gray-400 hover:text-gray-600">
                            <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"></path>
                            </svg>
                        </button>
                    </div>

                    <form wire:submit="save" class="space-y-4">
                        <div>
                            <label for="title" class="block text-sm font-medium text-gray-700 mb-1">
                                Görev Başlığı *
                            </label>
                            <input type="text" id="title" wire:model="title"
                                   class="w-full px-3 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-2 focus:ring-blue-500"
                                   placeholder="Görev başlığını girin">
                            @error('title') <span class="text-red-500 text-sm">{{ $message }}</span> @enderror
                        </div>

                        <div>
                            <label for="description" class="block text-sm font-medium text-gray-700 mb-1">
                                Açıklama
                            </label>
                            <textarea id="description" wire:model="description" rows="3"
                                      class="w-full px-3 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-2 focus:ring-blue-500"
                                      placeholder="Görev açıklaması (opsiyonel)"></textarea>
                            @error('description') <span class="text-red-500 text-sm">{{ $message }}</span> @enderror
                        </div>

                        <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                            <div>
                                <label for="priority" class="block text-sm font-medium text-gray-700 mb-1">
                                    Öncelik
                                </label>
                                <select id="priority" wire:model="priority"
                                        class="w-full px-3 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-2 focus:ring-blue-500">
                                    <option value="low">Düşük</option>
                                    <option value="medium">Orta</option>
                                    <option value="high">Yüksek</option>
                                    <option value="urgent">Acil</option>
                                </select>
                                @error('priority') <span class="text-red-500 text-sm">{{ $message }}</span> @enderror
                            </div>

                            <div>
                                <label for="task_status_id" class="block text-sm font-medium text-gray-700 mb-1">
                                    Durum
                                </label>
                                <select id="task_status_id" wire:model="task_status_id"
                                        class="w-full px-3 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-2 focus:ring-blue-500">
                                    @foreach($taskStatuses as $status)
                                        <option value="{{ $status->id }}">{{ $status->name }}</option>
                                    @endforeach
                                </select>
                                @error('task_status_id') <span class="text-red-500 text-sm">{{ $message }}</span> @enderror
                            </div>
                        </div>

                        <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                            <div>
                                <label for="assigned_to" class="block text-sm font-medium text-gray-700 mb-1">
                                    Atanacak Kişi
                                </label>
                                <select id="assigned_to" wire:model="assigned_to"
                                        class="w-full px-3 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-2 focus:ring-blue-500">
                                    <option value="">Atanmamış</option>
                                    @foreach($projectUsers as $user)
                                        <option value="{{ $user->id }}">{{ $user->name }}</option>
                                    @endforeach
                                </select>
                                @error('assigned_to') <span class="text-red-500 text-sm">{{ $message }}</span> @enderror
                            </div>

                            <div>
                                <label for="due_date" class="block text-sm font-medium text-gray-700 mb-1">
                                    Bitiş Tarihi
                                </label>
                                <input type="date" id="due_date" wire:model="due_date"
                                       class="w-full px-3 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-2 focus:ring-blue-500">
                                @error('due_date') <span class="text-red-500 text-sm">{{ $message }}</span> @enderror
                            </div>
                        </div>

                        @if($parentTasks->count() > 0)
                            <div>
                                <label for="parent_task_id" class="block text-sm font-medium text-gray-700 mb-1">
                                    Ana Görev (Alt görev yapmak için)
                                </label>
                                <select id="parent_task_id" wire:model="parent_task_id"
                                        class="w-full px-3 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-2 focus:ring-blue-500">
                                    <option value="">Ana görev değil</option>
                                    @foreach($parentTasks as $parentTask)
                                        <option value="{{ $parentTask->id }}">{{ $parentTask->title }}</option>
                                    @endforeach
                                </select>
                                @error('parent_task_id') <span class="text-red-500 text-sm">{{ $message }}</span> @enderror
                            </div>
                        @endif

                        <!-- Tags Selection -->
                        @if($projectTags->count() > 0)
                            <div class="col-span-2">
                                <label class="block text-sm font-medium text-gray-700 mb-2">
                                    Etiketler
                                </label>
                                <div class="flex flex-wrap gap-2">
                                    @foreach($projectTags as $tag)
                                        <button type="button"
                                                wire:click="toggleTag({{ $tag->id }})"
                                                class="inline-flex items-center px-3 py-1 rounded-full text-sm font-medium border-2 transition-colors
                                                    {{ in_array($tag->id, $selectedTags) 
                                                        ? 'border-gray-400 text-white' 
                                                        : 'border-gray-200 text-gray-700 hover:border-gray-300' }}"
                                                style="background-color: {{ in_array($tag->id, $selectedTags) ? $tag->color : 'transparent' }}; 
                                                       color: {{ in_array($tag->id, $selectedTags) ? 'white' : $tag->color }};">
                                            <span class="w-2 h-2 rounded-full mr-2" style="background-color: {{ $tag->color }}"></span>
                                            {{ $tag->name }}
                                        </button>
                                    @endforeach
                                </div>
                                <p class="text-xs text-gray-500 mt-1">Görev için uygun etiketleri seçin</p>
                            </div>
                        @endif

                        <div class="col-span-2 flex justify-end space-x-3 pt-4">
                            <button type="button" wire:click="closeModal"
                                    class="px-4 py-2 bg-gray-500 text-white rounded hover:bg-gray-600 transition-colors">
                                İptal
                            </button>
                            <button type="submit"
                                    class="px-4 py-2 bg-blue-500 text-white rounded hover:bg-blue-600 transition-colors">
                                {{ $isEditing ? 'Güncelle' : 'Oluştur' }}
                            </button>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    @endif
</div>
