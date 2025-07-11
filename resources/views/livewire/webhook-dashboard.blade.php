<div class="py-6">
    <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
        <!-- Header -->
        <div class="bg-white overflow-hidden shadow-sm sm:rounded-lg mb-6">
            <div class="p-6">
                <div class="flex justify-between items-center mb-4">
                    <div>
                        <h2 class="text-2xl font-bold text-gray-900">Webhook Dashboard</h2>
                        <p class="text-gray-600">{{ $project->name }} projesi GitHub webhook olaylarını izleyin</p>
                    </div>
                    <div class="flex space-x-3">
                        <button wire:click="toggleFilters" 
                                class="px-4 py-2 bg-gray-500 text-white rounded hover:bg-gray-600 transition-colors">
                            {{ $showFilters ? 'Filtreleri Gizle' : 'Filtrele' }}
                        </button>
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

                <!-- Statistics Cards -->
                <div class="grid grid-cols-2 md:grid-cols-4 lg:grid-cols-7 gap-4 mb-6">
                    <div class="bg-blue-50 p-4 rounded-lg text-center">
                        <div class="text-2xl font-bold text-blue-600">{{ $stats['total'] }}</div>
                        <div class="text-sm text-blue-700">Toplam</div>
                    </div>
                    <div class="bg-yellow-50 p-4 rounded-lg text-center">
                        <div class="text-2xl font-bold text-yellow-600">{{ $stats['pending'] }}</div>
                        <div class="text-sm text-yellow-700">Beklemede</div>
                    </div>
                    <div class="bg-green-50 p-4 rounded-lg text-center">
                        <div class="text-2xl font-bold text-green-600">{{ $stats['processed'] }}</div>
                        <div class="text-sm text-green-700">İşlendi</div>
                    </div>
                    <div class="bg-red-50 p-4 rounded-lg text-center">
                        <div class="text-2xl font-bold text-red-600">{{ $stats['failed'] }}</div>
                        <div class="text-sm text-red-700">Başarısız</div>
                    </div>
                    <div class="bg-gray-50 p-4 rounded-lg text-center">
                        <div class="text-2xl font-bold text-gray-600">{{ $stats['skipped'] }}</div>
                        <div class="text-sm text-gray-700">Atlandı</div>
                    </div>
                    <div class="bg-purple-50 p-4 rounded-lg text-center">
                        <div class="text-2xl font-bold text-purple-600">{{ $stats['today'] }}</div>
                        <div class="text-sm text-purple-700">Bugün</div>
                    </div>
                    <div class="bg-indigo-50 p-4 rounded-lg text-center">
                        <div class="text-2xl font-bold text-indigo-600">{{ $stats['this_week'] }}</div>
                        <div class="text-sm text-indigo-700">Bu Hafta</div>
                    </div>
                </div>

                <!-- Event Type Statistics -->
                @if(!empty($eventTypeStats))
                    <div class="mb-6">
                        <h3 class="text-lg font-semibold mb-3">Olay Türü İstatistikleri</h3>
                        <div class="grid grid-cols-2 md:grid-cols-4 gap-4">
                            @foreach($eventTypeStats as $eventType => $count)
                                <div class="bg-gray-50 p-3 rounded-lg text-center">
                                    <div class="text-lg font-bold text-gray-800">{{ $count }}</div>
                                    <div class="text-sm text-gray-600">{{ $eventType }}</div>
                                </div>
                            @endforeach
                        </div>
                    </div>
                @endif

                <!-- Filters -->
                @if($showFilters)
                    <div class="bg-gray-50 rounded-lg p-4 mb-6">
                        <div class="grid grid-cols-1 md:grid-cols-5 gap-4">
                            <div>
                                <label class="block text-sm font-medium text-gray-700 mb-2">Repository</label>
                                <select wire:model.live="selectedRepository" 
                                        class="w-full border border-gray-300 rounded-md shadow-sm py-2 px-3 focus:outline-none focus:ring-blue-500 focus:border-blue-500">
                                    <option value="">Tümü</option>
                                    @foreach($repositories as $repo)
                                        <option value="{{ $repo->id }}">{{ $repo->full_name }}</option>
                                    @endforeach
                                </select>
                            </div>

                            <div>
                                <label class="block text-sm font-medium text-gray-700 mb-2">Durum</label>
                                <select wire:model.live="selectedStatus" 
                                        class="w-full border border-gray-300 rounded-md shadow-sm py-2 px-3 focus:outline-none focus:ring-blue-500 focus:border-blue-500">
                                    <option value="">Tümü</option>
                                    @foreach($statusOptions as $status => $label)
                                        <option value="{{ $status }}">{{ $label }}</option>
                                    @endforeach
                                </select>
                            </div>

                            <div>
                                <label class="block text-sm font-medium text-gray-700 mb-2">Olay Türü</label>
                                <select wire:model.live="selectedEventType" 
                                        class="w-full border border-gray-300 rounded-md shadow-sm py-2 px-3 focus:outline-none focus:ring-blue-500 focus:border-blue-500">
                                    <option value="">Tümü</option>
                                    @foreach($eventTypes as $eventType)
                                        <option value="{{ $eventType }}">{{ $eventType }}</option>
                                    @endforeach
                                </select>
                            </div>

                            <div>
                                <label class="block text-sm font-medium text-gray-700 mb-2">Başlangıç</label>
                                <input type="date" 
                                       wire:model.live="dateFrom" 
                                       class="w-full border border-gray-300 rounded-md shadow-sm py-2 px-3 focus:outline-none focus:ring-blue-500 focus:border-blue-500">
                            </div>

                            <div>
                                <label class="block text-sm font-medium text-gray-700 mb-2">Bitiş</label>
                                <input type="date" 
                                       wire:model.live="dateTo" 
                                       class="w-full border border-gray-300 rounded-md shadow-sm py-2 px-3 focus:outline-none focus:ring-blue-500 focus:border-blue-500">
                            </div>
                        </div>

                        <div class="mt-4 flex justify-end">
                            <button wire:click="clearFilters" 
                                    class="px-4 py-2 bg-gray-500 text-white rounded hover:bg-gray-600">
                                Filtreleri Temizle
                            </button>
                        </div>
                    </div>
                @endif
            </div>
        </div>

        <!-- Webhooks List -->
        <div class="bg-white overflow-hidden shadow-sm sm:rounded-lg">
            <div class="p-6">
                <h3 class="text-lg font-semibold mb-4">Webhook Olayları</h3>
                
                @if($webhooks->count() > 0)
                    <div class="overflow-x-auto">
                        <table class="min-w-full divide-y divide-gray-200">
                            <thead class="bg-gray-50">
                                <tr>
                                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Olay</th>
                                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Repository</th>
                                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Durum</th>
                                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Tarih</th>
                                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">İşlemler</th>
                                </tr>
                            </thead>
                            <tbody class="bg-white divide-y divide-gray-200">
                                @foreach($webhooks as $webhook)
                                    <tr class="hover:bg-gray-50">
                                        <td class="px-6 py-4 whitespace-nowrap">
                                            <div class="flex items-center">
                                                <div>
                                                    <div class="text-sm font-medium text-gray-900">
                                                        {{ $webhook->event_type }}
                                                        @if($webhook->action)
                                                            <span class="text-gray-500">: {{ $webhook->action }}</span>
                                                        @endif
                                                    </div>
                                                    <div class="text-sm text-gray-500">ID: {{ $webhook->github_delivery_id }}</div>
                                                </div>
                                            </div>
                                        </td>
                                        <td class="px-6 py-4 whitespace-nowrap">
                                            <div class="text-sm text-gray-900">{{ $webhook->githubRepository->full_name }}</div>
                                        </td>
                                        <td class="px-6 py-4 whitespace-nowrap">
                                            @php
                                                $statusColors = [
                                                    'pending' => 'bg-yellow-100 text-yellow-800',
                                                    'processed' => 'bg-green-100 text-green-800',
                                                    'failed' => 'bg-red-100 text-red-800',
                                                    'skipped' => 'bg-gray-100 text-gray-800',
                                                ];
                                            @endphp
                                            <span class="px-2 inline-flex text-xs leading-5 font-semibold rounded-full {{ $statusColors[$webhook->status] }}">
                                                {{ $statusOptions[$webhook->status] }}
                                            </span>
                                        </td>
                                        <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500">
                                            <div>{{ $webhook->created_at->format('d.m.Y H:i') }}</div>
                                            @if($webhook->processed_at)
                                                <div class="text-xs">İşlendi: {{ $webhook->processed_at->format('d.m.Y H:i') }}</div>
                                            @endif
                                        </td>
                                        <td class="px-6 py-4 whitespace-nowrap text-sm font-medium">
                                            <div class="flex space-x-2">
                                                <button wire:click="viewWebhookDetails({{ $webhook->id }})"
                                                        class="text-blue-600 hover:text-blue-900">
                                                    Detay
                                                </button>
                                                @if($webhook->status === 'failed' || $webhook->status === 'skipped')
                                                    <button wire:click="reprocessWebhook({{ $webhook->id }})"
                                                            class="text-green-600 hover:text-green-900">
                                                        Yeniden İşle
                                                    </button>
                                                @endif
                                                <button wire:click="deleteWebhook({{ $webhook->id }})"
                                                        onclick="return confirm('Bu webhook kaydını silmek istediğinizden emin misiniz?')"
                                                        class="text-red-600 hover:text-red-900">
                                                    Sil
                                                </button>
                                            </div>
                                        </td>
                                    </tr>
                                @endforeach
                            </tbody>
                        </table>
                    </div>

                    <!-- Pagination -->
                    <div class="mt-4">
                        {{ $webhooks->links() }}
                    </div>
                @else
                    <div class="text-center py-12">
                        <svg class="mx-auto h-12 w-12 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"></path>
                        </svg>
                        <h3 class="mt-2 text-sm font-medium text-gray-900">Webhook olayı bulunamadı</h3>
                        <p class="mt-1 text-sm text-gray-500">
                            GitHub repository'lerinizden henüz webhook olayı gelmemiş.
                        </p>
                    </div>
                @endif
            </div>
        </div>
    </div>

    <!-- Webhook Details Modal -->
    @if($showWebhookDetails && $selectedWebhook)
        <div class="fixed inset-0 bg-gray-600 bg-opacity-50 overflow-y-auto h-full w-full z-50" wire:click="closeWebhookDetails">
            <div class="relative top-20 mx-auto p-5 border w-11/12 max-w-4xl shadow-lg rounded-md bg-white" wire:click.stop>
                <div class="flex justify-between items-center mb-4">
                    <h3 class="text-lg font-semibold">Webhook Detayları</h3>
                    <button wire:click="closeWebhookDetails" class="text-gray-500 hover:text-gray-700">
                        <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"></path>
                        </svg>
                    </button>
                </div>

                <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                    <!-- Basic Info -->
                    <div>
                        <h4 class="font-semibold mb-3">Temel Bilgiler</h4>
                        <div class="space-y-2 text-sm">
                            <div><strong>Olay Türü:</strong> {{ $selectedWebhook->event_type }}</div>
                            @if($selectedWebhook->action)
                                <div><strong>Aksiyon:</strong> {{ $selectedWebhook->action }}</div>
                            @endif
                            <div><strong>Repository:</strong> {{ $selectedWebhook->githubRepository->full_name }}</div>
                            <div><strong>Durum:</strong> 
                                <span class="px-2 py-1 text-xs rounded-full {{ $statusColors[$selectedWebhook->status] }}">
                                    {{ $statusOptions[$selectedWebhook->status] }}
                                </span>
                            </div>
                            <div><strong>GitHub Delivery ID:</strong> {{ $selectedWebhook->github_delivery_id }}</div>
                            <div><strong>Oluşturulma:</strong> {{ $selectedWebhook->created_at->format('d.m.Y H:i:s') }}</div>
                            @if($selectedWebhook->processed_at)
                                <div><strong>İşlenme:</strong> {{ $selectedWebhook->processed_at->format('d.m.Y H:i:s') }}</div>
                            @endif
                        </div>
                    </div>

                    <!-- Processing Result -->
                    <div>
                        <h4 class="font-semibold mb-3">İşlem Sonucu</h4>
                        @if($selectedWebhook->error_message)
                            <div class="bg-red-50 border border-red-200 rounded p-3 text-sm">
                                <strong class="text-red-700">Hata:</strong>
                                <div class="text-red-600 mt-1">{{ $selectedWebhook->error_message }}</div>
                            </div>
                        @elseif($selectedWebhook->processing_result)
                            <div class="bg-green-50 border border-green-200 rounded p-3 text-sm">
                                <strong class="text-green-700">Sonuç:</strong>
                                <pre class="text-green-600 mt-1 text-xs overflow-x-auto">{{ json_encode($selectedWebhook->processing_result, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE) }}</pre>
                            </div>
                        @else
                            <div class="text-gray-500 text-sm">Henüz işlenmemiş</div>
                        @endif
                    </div>
                </div>

                <!-- Payload -->
                <div class="mt-6">
                    <h4 class="font-semibold mb-3">Webhook Payload</h4>
                    <div class="bg-gray-50 border rounded p-4 max-h-96 overflow-y-auto">
                        <pre class="text-xs text-gray-700">{{ json_encode($selectedWebhook->payload, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE) }}</pre>
                    </div>
                </div>

                <!-- Actions -->
                <div class="mt-6 flex justify-end space-x-3">
                    @if($selectedWebhook->status === 'failed' || $selectedWebhook->status === 'skipped')
                        <button wire:click="reprocessWebhook({{ $selectedWebhook->id }})"
                                class="px-4 py-2 bg-green-600 text-white rounded hover:bg-green-700">
                            Yeniden İşle
                        </button>
                    @endif
                    <button wire:click="closeWebhookDetails"
                            class="px-4 py-2 bg-gray-500 text-white rounded hover:bg-gray-600">
                        Kapat
                    </button>
                </div>
            </div>
        </div>
    @endif
</div>