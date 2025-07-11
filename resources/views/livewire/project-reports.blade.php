<div class="py-6">
    <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
        <!-- Header -->
        <div class="bg-white overflow-hidden shadow-sm sm:rounded-lg mb-6">
            <div class="p-6">
                <div class="flex justify-between items-center mb-4">
                    <div>
                        <h2 class="text-2xl font-bold text-gray-900 flex items-center">
                            <svg class="w-6 h-6 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 17v-2m3 2v-4m3 4v-6m2 10H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"></path>
                            </svg>
                            Proje Raporları
                        </h2>
                        <p class="text-gray-600">{{ $project->name }} projesi detaylı analiz raporları</p>
                    </div>
                    <div class="flex space-x-3">
                        <a href="/projects/{{ $project->id }}/analytics" class="px-4 py-2 bg-orange-500 text-white rounded hover:bg-orange-600 transition-colors">
                            Analitikler
                        </a>
                        <a href="/projects/{{ $project->id }}" class="px-4 py-2 bg-blue-500 text-white rounded hover:bg-blue-600 transition-colors">
                            Kanban Board
                        </a>
                    </div>
                </div>

                <!-- Report Controls -->
                <div class="flex flex-wrap items-center justify-between gap-4 mb-6">
                    <div class="flex flex-wrap items-center gap-4">
                        <!-- Date Range -->
                        <div>
                            <label class="block text-sm font-medium text-gray-700 mb-1">Rapor Dönemi</label>
                            <select wire:model.live="dateRange" class="border border-gray-300 rounded-md px-3 py-2 focus:outline-none focus:ring-2 focus:ring-blue-500">
                                <option value="7">Son 7 Gün</option>
                                <option value="30">Son 30 Gün</option>
                                <option value="90">Son 90 Gün</option>
                                <option value="365">Son 1 Yıl</option>
                                <option value="all">Tümü</option>
                            </select>
                        </div>

                        <!-- Report Type -->
                        <div>
                            <label class="block text-sm font-medium text-gray-700 mb-1">Rapor Türü</label>
                            <select wire:model.live="reportType" class="border border-gray-300 rounded-md px-3 py-2 focus:outline-none focus:ring-2 focus:ring-blue-500">
                                <option value="progress">İlerleme Raporu</option>
                                <option value="performance">Performans Raporu</option>
                                <option value="summary">Özet Raporu</option>
                            </select>
                        </div>

                        <!-- Generate Report Button -->
                        <div class="flex items-end">
                            <button wire:click="generateReport" 
                                    wire:loading.attr="disabled"
                                    class="px-4 py-2 bg-green-600 text-white rounded hover:bg-green-700 disabled:opacity-50 transition-colors">
                                <span wire:loading.remove wire:target="generateReport">Rapor Oluştur</span>
                                <span wire:loading wire:target="generateReport">Oluşturuluyor...</span>
                            </button>
                        </div>
                    </div>

                    <!-- Export Controls -->
                    @if($reportSummary)
                        <div class="flex space-x-2">
                            <button wire:click="exportReport('pdf')" class="px-3 py-2 bg-red-500 text-white rounded hover:bg-red-600 transition-colors text-sm">
                                PDF İndir
                            </button>
                            <button wire:click="exportReport('excel')" class="px-3 py-2 bg-green-500 text-white rounded hover:bg-green-600 transition-colors text-sm">
                                Excel İndir
                            </button>
                            <button wire:click="toggleDetailedView" class="px-3 py-2 bg-gray-500 text-white rounded hover:bg-gray-600 transition-colors text-sm">
                                {{ $showDetailedView ? 'Özet Görünüm' : 'Detaylı Görünüm' }}
                            </button>
                        </div>
                    @endif
                </div>

                <!-- Success/Error Messages -->
                @if (session()->has('message'))
                    <div class="bg-green-100 border border-green-400 text-green-700 px-4 py-3 rounded mb-4">
                        {{ session('message') }}
                    </div>
                @endif
            </div>
        </div>

        @if($reportSummary)
            <!-- Executive Summary -->
            <div class="bg-white overflow-hidden shadow-sm sm:rounded-lg mb-6">
                <div class="p-6">
                    <h3 class="text-lg font-semibold text-gray-900 mb-4">Yönetici Özeti</h3>
                    
                    <!-- Key Metrics -->
                    <div class="grid grid-cols-2 md:grid-cols-4 gap-4 mb-6">
                        <div class="bg-blue-50 p-4 rounded-lg text-center">
                            <div class="text-2xl font-bold text-blue-600">{{ $reportSummary['completion_rate'] }}%</div>
                            <div class="text-sm text-blue-700">Tamamlanma Oranı</div>
                        </div>
                        <div class="bg-green-50 p-4 rounded-lg text-center">
                            <div class="text-2xl font-bold text-green-600">{{ $reportSummary['velocity'] }}</div>
                            <div class="text-sm text-green-700">Haftalık Hız</div>
                        </div>
                        <div class="bg-yellow-50 p-4 rounded-lg text-center">
                            <div class="text-2xl font-bold text-yellow-600">{{ $reportSummary['overdue_tasks'] }}</div>
                            <div class="text-sm text-yellow-700">Geciken Görevler</div>
                        </div>
                        <div class="bg-purple-50 p-4 rounded-lg text-center">
                            <div class="text-2xl font-bold text-purple-600">{{ $reportSummary['team_size'] }}</div>
                            <div class="text-sm text-purple-700">Ekip Üyesi</div>
                        </div>
                    </div>

                    <!-- Critical Issues -->
                    @if(count($reportSummary['high_priority_recommendations']) > 0)
                        <div class="bg-red-50 border-l-4 border-red-400 p-4 mb-4">
                            <div class="flex">
                                <div class="flex-shrink-0">
                                    <svg class="h-5 w-5 text-red-400" fill="currentColor" viewBox="0 0 20 20">
                                        <path fill-rule="evenodd" d="M8.257 3.099c.765-1.36 2.722-1.36 3.486 0l5.58 9.92c.75 1.334-.213 2.98-1.742 2.98H4.42c-1.53 0-2.493-1.646-1.743-2.98l5.58-9.92zM11 13a1 1 0 11-2 0 1 1 0 012 0zm-1-8a1 1 0 00-1 1v3a1 1 0 002 0V6a1 1 0 00-1-1z" clip-rule="evenodd"/>
                                    </svg>
                                </div>
                                <div class="ml-3">
                                    <h4 class="text-sm font-medium text-red-800">Acil Dikkat Gereken Konular</h4>
                                    <div class="mt-2 text-sm text-red-700">
                                        <ul class="list-disc list-inside space-y-1">
                                            @foreach($reportSummary['high_priority_recommendations'] as $rec)
                                                <li>{{ $rec['title'] }}: {{ $rec['description'] }}</li>
                                            @endforeach
                                        </ul>
                                    </div>
                                </div>
                            </div>
                        </div>
                    @endif

                    <!-- Project Health Status -->
                    <div class="flex items-center justify-between p-4 bg-gray-50 rounded-lg">
                        <div>
                            <h4 class="font-medium text-gray-900">Proje Sağlık Durumu</h4>
                            <p class="text-sm text-gray-600">Genel performans değerlendirmesi</p>
                        </div>
                        <div class="flex items-center space-x-2">
                            @php
                                $healthScore = $reportSummary['completion_rate'];
                                if ($healthScore >= 80) {
                                    $healthColor = 'green';
                                    $healthText = 'Mükemmel';
                                } elseif ($healthScore >= 60) {
                                    $healthColor = 'yellow';
                                    $healthText = 'İyi';
                                } elseif ($healthScore >= 40) {
                                    $healthColor = 'orange';
                                    $healthText = 'Orta';
                                } else {
                                    $healthColor = 'red';
                                    $healthText = 'Düşük';
                                }
                            @endphp
                            <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium bg-{{ $healthColor }}-100 text-{{ $healthColor }}-800">
                                {{ $healthText }}
                            </span>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Progress Timeline -->
            @if(count($timelineData) > 0)
                <div class="bg-white overflow-hidden shadow-sm sm:rounded-lg mb-6">
                    <div class="p-6">
                        <h3 class="text-lg font-semibold text-gray-900 mb-4">İlerleme Zaman Çizelgesi</h3>
                        
                        <div class="overflow-x-auto">
                            <table class="min-w-full divide-y divide-gray-200">
                                <thead class="bg-gray-50">
                                    <tr>
                                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Hafta</th>
                                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Oluşturulan</th>
                                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Tamamlanan</th>
                                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Net İlerleme</th>
                                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Kümülatif</th>
                                    </tr>
                                </thead>
                                <tbody class="bg-white divide-y divide-gray-200">
                                    @foreach(array_slice($timelineData, -8) as $week)
                                        <tr>
                                            <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-900">
                                                {{ \Carbon\Carbon::parse($week['week_start'])->format('d.m') }} - 
                                                {{ \Carbon\Carbon::parse($week['week_end'])->format('d.m') }}
                                            </td>
                                            <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-900">{{ $week['tasks_created'] }}</td>
                                            <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-900">{{ $week['tasks_completed'] }}</td>
                                            <td class="px-6 py-4 whitespace-nowrap text-sm">
                                                <span class="text-{{ $week['net_progress'] >= 0 ? 'green' : 'red' }}-600 font-medium">
                                                    {{ $week['net_progress'] > 0 ? '+' : '' }}{{ $week['net_progress'] }}
                                                </span>
                                            </td>
                                            <td class="px-6 py-4 whitespace-nowrap text-sm font-medium text-blue-600">{{ $week['cumulative_completed'] }}</td>
                                        </tr>
                                    @endforeach
                                </tbody>
                            </table>
                        </div>
                    </div>
                </div>
            @endif

            <!-- Team Performance -->
            @if(count($topPerformers) > 0)
                <div class="bg-white overflow-hidden shadow-sm sm:rounded-lg mb-6">
                    <div class="p-6">
                        <h3 class="text-lg font-semibold text-gray-900 mb-4">Ekip Performansı</h3>
                        
                        <div class="space-y-4">
                            @foreach($topPerformers as $performer)
                                <div class="flex items-center justify-between p-4 bg-gray-50 rounded-lg">
                                    <div class="flex items-center space-x-3">
                                        <div class="w-10 h-10 bg-blue-500 rounded-full flex items-center justify-center text-white font-medium">
                                            {{ substr($performer['user']->name, 0, 1) }}
                                        </div>
                                        <div>
                                            <div class="font-medium text-gray-900">{{ $performer['user']->name }}</div>
                                            <div class="text-sm text-gray-500">{{ $performer['rating'] }}</div>
                                        </div>
                                    </div>
                                    <div class="grid grid-cols-3 gap-4 text-center">
                                        <div>
                                            <div class="text-lg font-bold text-blue-600">{{ $performer['completed_tasks'] }}</div>
                                            <div class="text-xs text-gray-500">Tamamlanan</div>
                                        </div>
                                        <div>
                                            <div class="text-lg font-bold text-green-600">{{ $performer['completion_rate'] }}%</div>
                                            <div class="text-xs text-gray-500">Oran</div>
                                        </div>
                                        <div>
                                            <div class="text-lg font-bold text-purple-600">{{ $performer['productivity_score'] }}</div>
                                            <div class="text-xs text-gray-500">Puan</div>
                                        </div>
                                    </div>
                                </div>
                            @endforeach
                        </div>
                    </div>
                </div>
            @endif

            <!-- Bottlenecks and Issues -->
            @if(count($bottlenecks) > 0)
                <div class="bg-white overflow-hidden shadow-sm sm:rounded-lg mb-6">
                    <div class="p-6">
                        <h3 class="text-lg font-semibold text-gray-900 mb-4">Darboğazlar ve Sorunlar</h3>
                        
                        <div class="space-y-3">
                            @foreach($bottlenecks as $bottleneck)
                                <div class="border-l-4 border-{{ $bottleneck['severity'] === 'high' ? 'red' : ($bottleneck['severity'] === 'medium' ? 'yellow' : 'blue') }}-400 bg-{{ $bottleneck['severity'] === 'high' ? 'red' : ($bottleneck['severity'] === 'medium' ? 'yellow' : 'blue') }}-50 p-4">
                                    <div class="flex justify-between items-start">
                                        <div class="flex-1">
                                            <h4 class="font-medium text-gray-900">{{ $bottleneck['description'] }}</h4>
                                            <p class="text-sm text-gray-600 mt-1">{{ $bottleneck['affected_tasks'] }} görev etkileniyor</p>
                                        </div>
                                        <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium bg-{{ $bottleneck['severity'] === 'high' ? 'red' : ($bottleneck['severity'] === 'medium' ? 'yellow' : 'blue') }}-100 text-{{ $bottleneck['severity'] === 'high' ? 'red' : ($bottleneck['severity'] === 'medium' ? 'yellow' : 'blue') }}-800">
                                            {{ $bottleneck['severity'] === 'high' ? 'Yüksek' : ($bottleneck['severity'] === 'medium' ? 'Orta' : 'Düşük') }}
                                        </span>
                                    </div>
                                </div>
                            @endforeach
                        </div>
                    </div>
                </div>
            @endif

            <!-- Recommendations -->
            @if(count($recommendations) > 0)
                <div class="bg-white overflow-hidden shadow-sm sm:rounded-lg mb-6">
                    <div class="p-6">
                        <h3 class="text-lg font-semibold text-gray-900 mb-4">Öneriler ve Aksiyon Planı</h3>
                        
                        <div class="space-y-4">
                            @foreach($recommendations as $rec)
                                <div class="border border-gray-200 rounded-lg p-4">
                                    <div class="flex justify-between items-start mb-2">
                                        <h4 class="font-medium text-gray-900">{{ $rec['title'] }}</h4>
                                        <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium bg-{{ $rec['priority'] === 'high' ? 'red' : ($rec['priority'] === 'medium' ? 'yellow' : 'green') }}-100 text-{{ $rec['priority'] === 'high' ? 'red' : ($rec['priority'] === 'medium' ? 'yellow' : 'green') }}-800">
                                            {{ $rec['priority'] === 'high' ? 'Yüksek' : ($rec['priority'] === 'medium' ? 'Orta' : 'Düşük') }}
                                        </span>
                                    </div>
                                    <p class="text-sm text-gray-600 mb-2">{{ $rec['description'] }}</p>
                                    <div class="bg-blue-50 p-3 rounded">
                                        <p class="text-sm text-blue-800"><strong>Önerilen Aksiyon:</strong> {{ $rec['action'] }}</p>
                                    </div>
                                </div>
                            @endforeach
                        </div>
                    </div>
                </div>
            @endif

            <!-- Detailed Views (when enabled) -->
            @if($showDetailedView)
                <!-- Milestone Progress -->
                @if(count($milestones) > 0)
                    <div class="bg-white overflow-hidden shadow-sm sm:rounded-lg mb-6">
                        <div class="p-6">
                            <h3 class="text-lg font-semibold text-gray-900 mb-4">Milestone İlerlemesi</h3>
                            
                            <div class="space-y-4">
                                @foreach($milestones as $milestone)
                                    <div class="border border-gray-200 rounded-lg p-4">
                                        <div class="flex justify-between items-center mb-2">
                                            <h4 class="font-medium text-gray-900">{{ $milestone['name'] }}</h4>
                                            <span class="text-sm text-gray-500">{{ $milestone['progress_percentage'] }}%</span>
                                        </div>
                                        <div class="w-full bg-gray-200 rounded-full h-2 mb-2">
                                            <div class="bg-blue-600 h-2 rounded-full" style="width: {{ $milestone['progress_percentage'] }}%"></div>
                                        </div>
                                        <div class="flex justify-between text-sm text-gray-600">
                                            <span>{{ $milestone['completed_tasks'] }}/{{ $milestone['total_tasks'] }} görev</span>
                                            <span class="font-medium">{{ $milestone['status'] }}</span>
                                        </div>
                                    </div>
                                @endforeach
                            </div>
                        </div>
                    </div>
                @endif

                <!-- Task Breakdown -->
                @if($taskBreakdown)
                    <div class="bg-white overflow-hidden shadow-sm sm:rounded-lg mb-6">
                        <div class="p-6">
                            <h3 class="text-lg font-semibold text-gray-900 mb-4">Görev Dağılım Analizi</h3>
                            
                            <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                                <!-- Status Breakdown -->
                                <div>
                                    <h4 class="font-medium text-gray-700 mb-3">Durum Dağılımı</h4>
                                    <div class="space-y-2">
                                        @foreach($taskBreakdown['status_breakdown'] as $status => $count)
                                            <div class="flex justify-between items-center">
                                                <span class="text-sm text-gray-600">{{ $status }}</span>
                                                <span class="text-sm font-medium text-gray-900">{{ $count }}</span>
                                            </div>
                                        @endforeach
                                    </div>
                                </div>

                                <!-- Priority Breakdown -->
                                <div>
                                    <h4 class="font-medium text-gray-700 mb-3">Öncelik Dağılımı</h4>
                                    <div class="space-y-2">
                                        @foreach($taskBreakdown['priority_breakdown'] as $priority => $count)
                                            <div class="flex justify-between items-center">
                                                <span class="text-sm text-gray-600">{{ ucfirst($priority) }}</span>
                                                <span class="text-sm font-medium text-gray-900">{{ $count }}</span>
                                            </div>
                                        @endforeach
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                @endif
            @endif
        @else
            <!-- No Report Generated Yet -->
            <div class="bg-white overflow-hidden shadow-sm sm:rounded-lg">
                <div class="p-6 text-center">
                    <svg class="mx-auto h-12 w-12 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1" d="M9 17v-2m3 2v-4m3 4v-6m2 10H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"></path>
                    </svg>
                    <h3 class="mt-2 text-sm font-medium text-gray-900">Rapor Henüz Oluşturulmadı</h3>
                    <p class="mt-1 text-sm text-gray-500">
                        Proje için detaylı rapor oluşturmak için yukarıdaki "Rapor Oluştur" butonuna tıklayın.
                    </p>
                    <div class="mt-6">
                        <button wire:click="generateReport" 
                                class="inline-flex items-center px-4 py-2 border border-transparent shadow-sm text-sm font-medium rounded-md text-white bg-blue-600 hover:bg-blue-700">
                            Rapor Oluştur
                        </button>
                    </div>
                </div>
            </div>
        @endif
    </div>
</div>