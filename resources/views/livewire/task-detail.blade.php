<div>
    @if($showModal && $task)
        <div class="fixed inset-0 bg-gray-600 bg-opacity-50 overflow-y-auto h-full w-full z-50" wire:click="closeModal">
            <div class="relative top-10 mx-auto p-5 border w-11/12 md:w-3/4 lg:w-2/3 shadow-lg rounded-md bg-white max-h-[90vh] overflow-y-auto" wire:click.stop>
                <div class="mt-3">
                    <!-- Header -->
                    <div class="flex justify-between items-start mb-6">
                        <div class="flex-1">
                            <div class="flex items-center mb-2">
                                <span class="inline-flex items-center px-2 py-1 rounded-full text-xs font-medium mr-3
                                    @if($task->priority === 'urgent') bg-red-100 text-red-800
                                    @elseif($task->priority === 'high') bg-orange-100 text-orange-800
                                    @elseif($task->priority === 'medium') bg-yellow-100 text-yellow-800
                                    @else bg-green-100 text-green-800
                                    @endif">
                                    {{ ucfirst($task->priority) }}
                                </span>
                                <span class="px-2 py-1 rounded text-xs font-medium" style="background-color: {{ $task->taskStatus->color }}20; color: {{ $task->taskStatus->color }}">
                                    {{ $task->taskStatus->name }}
                                </span>
                            </div>
                            <h2 class="text-xl font-bold text-gray-900 mb-2">{{ $task->title }}</h2>
                            @if($task->description)
                                <p class="text-gray-600">{{ $task->description }}</p>
                            @endif
                        </div>
                        <button wire:click="closeModal" class="text-gray-400 hover:text-gray-600 ml-4">
                            <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"></path>
                            </svg>
                        </button>
                    </div>

                    <!-- Task Meta Information -->
                    <div class="grid grid-cols-1 md:grid-cols-3 gap-4 mb-6 p-4 bg-gray-50 rounded-lg">
                        <div>
                            <label class="block text-sm font-medium text-gray-700">Atanan Kişi</label>
                            <div class="mt-1">
                                @if($task->assignedUser)
                                    <div class="flex items-center">
                                        <div class="w-6 h-6 bg-blue-500 rounded-full flex items-center justify-center text-white text-xs font-medium mr-2">
                                            {{ substr($task->assignedUser->name, 0, 1) }}
                                        </div>
                                        <span class="text-sm">{{ $task->assignedUser->name }}</span>
                                    </div>
                                @else
                                    <span class="text-sm text-gray-500">Atanmamış</span>
                                @endif
                            </div>
                        </div>
                        
                        <div>
                            <label class="block text-sm font-medium text-gray-700">Bitiş Tarihi</label>
                            <div class="mt-1">
                                @if($task->due_date)
                                    <span class="text-sm @if($task->is_overdue) text-red-600 font-medium @endif">
                                        {{ $task->due_date->format('d.m.Y') }}
                                    </span>
                                @else
                                    <span class="text-sm text-gray-500">Belirlenmemiş</span>
                                @endif
                            </div>
                        </div>
                        
                        <div>
                            <label class="block text-sm font-medium text-gray-700">Oluşturan</label>
                            <div class="mt-1">
                                <span class="text-sm">{{ $task->createdBy->name }}</span>
                            </div>
                        </div>
                    </div>

                    <!-- Comments Section -->
                    <div class="border-t pt-6">
                        <h3 class="text-lg font-semibold text-gray-900 mb-4">
                            Yorumlar ({{ $task->comments->count() }})
                        </h3>

                        <!-- Add Comment Form -->
                        <div class="mb-6">
                            <form wire:submit="addComment">
                                <div class="mb-3">
                                    <textarea wire:model="newComment" 
                                              rows="3" 
                                              class="w-full px-3 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-2 focus:ring-blue-500"
                                              placeholder="Yorum ekleyin... (@kullanıcı_adı ile etiketleyebilirsiniz)"></textarea>
                                    @error('newComment') <span class="text-red-500 text-sm">{{ $message }}</span> @enderror
                                </div>
                                <button type="submit" 
                                        class="px-4 py-2 bg-blue-500 text-white rounded hover:bg-blue-600 transition-colors">
                                    Yorum Ekle
                                </button>
                            </form>
                        </div>

                        <!-- Comments List -->
                        <div class="space-y-4">
                            @forelse($task->comments as $comment)
                                <div class="bg-gray-50 rounded-lg p-4">
                                    <div class="flex justify-between items-start mb-2">
                                        <div class="flex items-center">
                                            <div class="w-8 h-8 bg-blue-500 rounded-full flex items-center justify-center text-white text-sm font-medium mr-3">
                                                {{ substr($comment->user->name, 0, 1) }}
                                            </div>
                                            <div>
                                                <div class="font-medium text-gray-900">{{ $comment->user->name }}</div>
                                                <div class="text-xs text-gray-500">{{ $comment->created_at->diffForHumans() }}</div>
                                            </div>
                                        </div>
                                        @if($comment->user_id === auth()->id())
                                            <button wire:click="deleteComment({{ $comment->id }})"
                                                    wire:confirm="Bu yorumu silmek istediğinizden emin misiniz?"
                                                    class="text-gray-400 hover:text-red-600 transition-colors">
                                                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16"></path>
                                                </svg>
                                            </button>
                                        @endif
                                    </div>
                                    <div class="text-gray-700 leading-relaxed">
                                        {!! $comment->formatted_content !!}
                                    </div>
                                </div>
                            @empty
                                <div class="text-center py-8 text-gray-500">
                                    <svg class="w-12 h-12 mx-auto mb-4 text-gray-300" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 12h.01M12 12h.01M16 12h.01M21 12c0 4.418-4.03 8-9 8a9.863 9.863 0 01-4.255-.949L3 20l1.395-3.72C3.512 15.042 3 13.574 3 12c0-4.418 4.03-8 9-8s9 3.582 9 8z"></path>
                                    </svg>
                                    <p>Henüz yorum yok. İlk yorumu siz ekleyin!</p>
                                </div>
                            @endforelse
                        </div>
                    </div>
                </div>
            </div>
        </div>
    @endif
</div>
