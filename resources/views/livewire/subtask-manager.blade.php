<div class="space-y-3">
    @if($task->hasSubtasks() || $showForm)
        <div class="border-t border-gray-200 pt-3">
            <!-- Subtask Progress -->
            @if($task->hasSubtasks())
                <div class="mb-3">
                    <div class="flex items-center justify-between text-xs text-gray-600 mb-1">
                        <span>Alt Görevler ({{ $subtasks->count() }})</span>
                        <span>{{ $completionPercentage }}% tamamlandı</span>
                    </div>
                    <div class="w-full bg-gray-200 rounded-full h-1.5">
                        <div class="bg-green-500 h-1.5 rounded-full transition-all duration-300" 
                             style="width: {{ $completionPercentage }}%"></div>
                    </div>
                </div>
            @endif

            <!-- Subtask List -->
            <div class="space-y-2">
                @foreach($subtasks as $subtask)
                    <div class="flex items-start space-x-2 p-2 bg-gray-50 rounded border" 
                         wire:key="subtask-{{ $subtask->id }}">
                        <!-- Completion Checkbox -->
                        <button wire:click="toggleSubtaskCompletion({{ $subtask->id }})"
                                class="mt-0.5 flex-shrink-0">
                            @if($subtask->is_completed)
                                <svg class="w-4 h-4 text-green-500" fill="currentColor" viewBox="0 0 20 20">
                                    <path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zm3.707-9.293a1 1 0 00-1.414-1.414L9 10.586 7.707 9.293a1 1 0 00-1.414 1.414l2 2a1 1 0 001.414 0l4-4z" clip-rule="evenodd"/>
                                </svg>
                            @else
                                <svg class="w-4 h-4 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <circle cx="12" cy="12" r="10" stroke-width="2" fill="none"/>
                                </svg>
                            @endif
                        </button>

                        <!-- Subtask Content -->
                        <div class="flex-1 min-w-0">
                            <div class="flex items-start justify-between">
                                <div class="flex-1">
                                    <h5 class="text-sm font-medium {{ $subtask->is_completed ? 'line-through text-gray-500' : 'text-gray-900' }}">
                                        {{ $subtask->title }}
                                    </h5>
                                    @if($subtask->description)
                                        <p class="text-xs text-gray-600 mt-1 {{ $subtask->is_completed ? 'line-through' : '' }}">
                                            {{ Str::limit($subtask->description, 100) }}
                                        </p>
                                    @endif
                                    <div class="flex items-center space-x-2 mt-1">
                                        <span class="inline-flex items-center px-2 py-0.5 rounded text-xs font-medium"
                                              style="background-color: {{ $subtask->priority_color }}20; color: {{ $subtask->priority_color }}">
                                            {{ ucfirst($subtask->priority) }}
                                        </span>
                                        @if($subtask->due_date)
                                            <span class="text-xs text-gray-500">
                                                {{ $subtask->due_date->format('M j') }}
                                            </span>
                                        @endif
                                    </div>
                                </div>

                                <!-- Subtask Actions -->
                                <div class="flex items-center space-x-1 ml-2">
                                    @if($subtask->subtask_order > 0)
                                        <button wire:click="moveSubtaskUp({{ $subtask->id }})"
                                                class="p-1 text-gray-400 hover:text-gray-600 rounded">
                                            <svg class="w-3 h-3" fill="currentColor" viewBox="0 0 20 20">
                                                <path fill-rule="evenodd" d="M14.707 12.707a1 1 0 01-1.414 0L10 9.414l-3.293 3.293a1 1 0 01-1.414-1.414l4-4a1 1 0 011.414 0l4 4a1 1 0 010 1.414z" clip-rule="evenodd"/>
                                            </svg>
                                        </button>
                                    @endif
                                    
                                    @if($subtask->subtask_order < $subtasks->count() - 1)
                                        <button wire:click="moveSubtaskDown({{ $subtask->id }})"
                                                class="p-1 text-gray-400 hover:text-gray-600 rounded">
                                            <svg class="w-3 h-3" fill="currentColor" viewBox="0 0 20 20">
                                                <path fill-rule="evenodd" d="M5.293 7.293a1 1 0 011.414 0L10 10.586l3.293-3.293a1 1 0 111.414 1.414l-4 4a1 1 0 01-1.414 0l-4-4a1 1 0 010-1.414z" clip-rule="evenodd"/>
                                            </svg>
                                        </button>
                                    @endif

                                    <button wire:click="deleteSubtask({{ $subtask->id }})"
                                            onclick="confirm('Bu alt görevi silmek istediğinizden emin misiniz?') || event.stopImmediatePropagation()"
                                            class="p-1 text-red-400 hover:text-red-600 rounded">
                                        <svg class="w-3 h-3" fill="currentColor" viewBox="0 0 20 20">
                                            <path fill-rule="evenodd" d="M9 2a1 1 0 000 2h2a1 1 0 100-2H9zM4 5a2 2 0 012-2h8a2 2 0 012 2v6a2 2 0 01-2 2H6a2 2 0 01-2-2V5zm3 4a1 1 0 102 0v3a1 1 0 11-2 0V9zm4 0a1 1 0 10-2 0v3a1 1 0 102 0V9z" clip-rule="evenodd"/>
                                        </svg>
                                    </button>
                                </div>
                            </div>
                        </div>
                    </div>
                @endforeach
            </div>

            <!-- Add Subtask Form -->
            @if($showForm)
                <div class="mt-3 p-3 bg-white border border-gray-200 rounded">
                    <form wire:submit="createSubtask" class="space-y-3">
                        <div>
                            <label class="block text-xs font-medium text-gray-700 mb-1">
                                Alt Görev Başlığı *
                            </label>
                            <input type="text" 
                                   wire:model="newSubtaskTitle"
                                   class="w-full px-2 py-1 text-sm border border-gray-300 rounded focus:ring-blue-500 focus:border-blue-500"
                                   placeholder="Alt görev başlığını girin">
                            @error('newSubtaskTitle')
                                <span class="text-xs text-red-600">{{ $message }}</span>
                            @enderror
                        </div>

                        <div>
                            <label class="block text-xs font-medium text-gray-700 mb-1">
                                Açıklama
                            </label>
                            <textarea wire:model="newSubtaskDescription"
                                      rows="2"
                                      class="w-full px-2 py-1 text-sm border border-gray-300 rounded focus:ring-blue-500 focus:border-blue-500"
                                      placeholder="Alt görev açıklaması (opsiyonel)"></textarea>
                        </div>

                        <div>
                            <label class="block text-xs font-medium text-gray-700 mb-1">
                                Öncelik
                            </label>
                            <select wire:model="newSubtaskPriority"
                                    class="w-full px-2 py-1 text-sm border border-gray-300 rounded focus:ring-blue-500 focus:border-blue-500">
                                <option value="low">Düşük</option>
                                <option value="medium">Orta</option>
                                <option value="high">Yüksek</option>
                                <option value="urgent">Acil</option>
                            </select>
                        </div>

                        <div class="flex justify-end space-x-2">
                            <button type="button" 
                                    wire:click="hideForm"
                                    class="px-3 py-1 text-xs text-gray-600 border border-gray-300 rounded hover:bg-gray-50">
                                İptal
                            </button>
                            <button type="submit"
                                    class="px-3 py-1 text-xs text-white bg-blue-600 rounded hover:bg-blue-700">
                                Ekle
                            </button>
                        </div>
                    </form>
                </div>
            @endif
        </div>
    @endif

    <!-- Add Subtask Button -->
    @if($task->canHaveSubtasks() && !$showForm)
        <button wire:click="showAddForm"
                class="w-full mt-2 px-3 py-2 text-xs text-gray-600 border border-dashed border-gray-300 rounded hover:border-blue-400 hover:text-blue-600 transition-colors">
            + Alt Görev Ekle
        </button>
    @endif

    @if(!$task->canHaveSubtasks())
        <div class="mt-2 text-xs text-gray-500 text-center">
            Maksimum alt görev derinliğine ulaşıldı
        </div>
    @endif
</div>