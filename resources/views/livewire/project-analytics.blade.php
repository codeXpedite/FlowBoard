<div class="py-6">
    <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
        <!-- Header -->
        <div class="bg-white overflow-hidden shadow-sm sm:rounded-lg mb-6">
            <div class="p-6">
                <div class="flex justify-between items-center mb-4">
                    <div>
                        <h2 class="text-2xl font-bold text-gray-900 flex items-center">
                            <svg class="w-6 h-6 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 19v-6a2 2 0 00-2-2H5a2 2 0 00-2 2v6a2 2 0 002 2h2a2 2 0 002-2zm0 0V9a2 2 0 012-2h2a2 2 0 012 2v10m-6 0a2 2 0 002 2h2a2 2 0 002-2m0 0V5a2 2 0 012-2h2a2 2 0 012 2v14a2 2 0 01-2 2h-2a2 2 0 01-2-2z"></path>
                            </svg>
                            Proje Analitikleri
                        </h2>
                        <p class="text-gray-600">{{ $project->name }} projesi performans analizi</p>
                    </div>
                    <div class="flex space-x-3">
                        <a href="/projects/{{ $project->id }}" class="px-4 py-2 bg-blue-500 text-white rounded hover:bg-blue-600 transition-colors">
                            Kanban Board
                        </a>
                        <a href="/projects/{{ $project->id }}/settings" class="px-4 py-2 bg-purple-500 text-white rounded hover:bg-purple-600 transition-colors">
                            Ayarlar
                        </a>
                    </div>
                </div>

                <!-- Filters -->
                <div class="flex flex-wrap items-center justify-between gap-4 mb-6">
                    <div class="flex flex-wrap items-center gap-4">
                        <!-- Date Range Filter -->
                        <div>
                            <label class="block text-sm font-medium text-gray-700 mb-1">Zaman Aralığı</label>
                            <select wire:model.live="dateRange" class="border border-gray-300 rounded-md px-3 py-2 focus:outline-none focus:ring-2 focus:ring-blue-500">
                                <option value="7">Son 7 Gün</option>
                                <option value="30">Son 30 Gün</option>
                                <option value="90">Son 90 Gün</option>
                                <option value="365">Son 1 Yıl</option>
                                <option value="all">Tümü</option>
                            </select>
                        </div>

                        <!-- Metric Selection -->
                        <div>
                            <label class="block text-sm font-medium text-gray-700 mb-1">Metrik</label>
                            <select wire:model.live="selectedMetric" class="border border-gray-300 rounded-md px-3 py-2 focus:outline-none focus:ring-2 focus:ring-blue-500">
                                <option value="overview">Genel Bakış</option>
                                <option value="tasks">Görev Analizi</option>
                                <option value="users">Kullanıcı Performansı</option>
                                <option value="trends">Verimlilik Trendleri</option>
                            </select>
                        </div>

                        <!-- User Filter -->
                        @if($selectedMetric === 'users' && $projectUsers->count() > 0)
                            <div>
                                <label class="block text-sm font-medium text-gray-700 mb-1">Kullanıcı</label>
                                <select wire:model.live="selectedUser" class="border border-gray-300 rounded-md px-3 py-2 focus:outline-none focus:ring-2 focus:ring-blue-500">
                                    <option value="">Tüm Kullanıcılar</option>
                                    @foreach($projectUsers as $user)
                                        <option value="{{ $user->id }}">{{ $user->name }}</option>
                                    @endforeach
                                </select>
                            </div>
                        @endif
                    </div>

                    <!-- Export and Advanced Filters -->
                    <div class="flex space-x-2">
                        <button wire:click="exportAnalytics('pdf')" class="px-3 py-2 bg-red-500 text-white rounded hover:bg-red-600 transition-colors text-sm">
                            PDF İndir
                        </button>
                        <button wire:click="toggleAdvancedFilters" class="px-4 py-2 bg-gray-100 text-gray-700 rounded hover:bg-gray-200 transition-colors">
                            {{ $showAdvancedFilters ? 'Basit Görünüm' : 'Gelişmiş Filtreler' }}
                        </button>
                    </div>
                </div>
            </div>
        </div>

        <!-- Overview Metrics -->
        @if($selectedMetric === 'overview')
            <!-- Key Metrics Cards -->
            <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-4 gap-6 mb-6">
                <div class="bg-white overflow-hidden shadow sm:rounded-lg">
                    <div class="p-5">
                        <div class="flex items-center">
                            <div class="flex-shrink-0">
                                <div class="w-8 h-8 bg-blue-500 rounded-full flex items-center justify-center">
                                    <svg class="w-4 h-4 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5H7a2 2 0 00-2 2v10a2 2 0 002 2h8a2 2 0 002-2V7a2 2 0 00-2-2h-2M9 5a2 2 0 002 2h2a2 2 0 002-2M9 5a2 2 0 012-2h2a2 2 0 012 2"></path>
                                    </svg>
                                </div>
                            </div>
                            <div class="ml-5 w-0 flex-1">
                                <dl>
                                    <dt class="text-sm font-medium text-gray-500 truncate">Toplam Görevler</dt>
                                    <dd class="text-lg font-medium text-gray-900">{{ $overviewMetrics['total_tasks'] }}</dd>
                                </dl>
                            </div>
                        </div>
                    </div>
                </div>

                <div class="bg-white overflow-hidden shadow sm:rounded-lg">
                    <div class="p-5">
                        <div class="flex items-center">
                            <div class="flex-shrink-0">
                                <div class="w-8 h-8 bg-green-500 rounded-full flex items-center justify-center">
                                    <svg class="w-4 h-4 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"></path>
                                    </svg>
                                </div>
                            </div>
                            <div class="ml-5 w-0 flex-1">
                                <dl>
                                    <dt class="text-sm font-medium text-gray-500 truncate">Tamamlanan</dt>
                                    <dd class="text-lg font-medium text-gray-900">{{ $overviewMetrics['completed_tasks'] }}</dd>
                                </dl>
                            </div>
                        </div>
                    </div>
                </div>

                <div class="bg-white overflow-hidden shadow sm:rounded-lg">
                    <div class="p-5">
                        <div class="flex items-center">
                            <div class="flex-shrink-0">
                                <div class="w-8 h-8 bg-yellow-500 rounded-full flex items-center justify-center">
                                    <svg class="w-4 h-4 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z"></path>
                                    </svg>
                                </div>
                            </div>
                            <div class="ml-5 w-0 flex-1">
                                <dl>
                                    <dt class="text-sm font-medium text-gray-500 truncate">Aktif Görevler</dt>
                                    <dd class="text-lg font-medium text-gray-900">{{ $overviewMetrics['active_tasks'] }}</dd>
                                </dl>
                            </div>
                        </div>
                    </div>
                </div>

                <div class="bg-white overflow-hidden shadow sm:rounded-lg">
                    <div class="p-5">
                        <div class="flex items-center">
                            <div class="flex-shrink-0">
                                <div class="w-8 h-8 bg-red-500 rounded-full flex items-center justify-center">
                                    <svg class="w-4 h-4 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-2.5L13.732 4c-.77-.833-1.964-.833-2.732 0L3.732 16.5c-.77.833.192 2.5 1.732 2.5z"></path>
                                    </svg>
                                </div>
                            </div>
                            <div class="ml-5 w-0 flex-1">
                                <dl>
                                    <dt class="text-sm font-medium text-gray-500 truncate">Geciken</dt>
                                    <dd class="text-lg font-medium text-gray-900">{{ $overviewMetrics['overdue_tasks'] }}</dd>
                                </dl>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Progress and Performance Metrics -->
            <div class="grid grid-cols-1 lg:grid-cols-3 gap-6 mb-6">
                <!-- Completion Rate -->
                <div class="bg-white overflow-hidden shadow sm:rounded-lg p-6">
                    <h3 class="text-lg font-medium text-gray-900 mb-4">Tamamlanma Oranı</h3>
                    <div class="flex items-center justify-center">
                        <div class="relative w-24 h-24">
                            <svg class="w-24 h-24 transform -rotate-90" viewBox="0 0 36 36">
                                <path d="m18 2.0845 a 15.9155 15.9155 0 0 1 0 31.831 a 15.9155 15.9155 0 0 1 0 -31.831"
                                      fill="none" stroke="#f3f4f6" stroke-width="2" />
                                <path d="m18 2.0845 a 15.9155 15.9155 0 0 1 0 31.831 a 15.9155 15.9155 0 0 1 0 -31.831"
                                      fill="none" stroke="#10b981" stroke-width="2"
                                      stroke-dasharray="{{ $overviewMetrics['completion_rate'] }}, 100" />
                            </svg>
                            <div class="absolute inset-0 flex items-center justify-center">
                                <span class="text-lg font-semibold text-gray-900">{{ $overviewMetrics['completion_rate'] }}%</span>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Average Completion Time -->
                <div class="bg-white overflow-hidden shadow sm:rounded-lg p-6">
                    <h3 class="text-lg font-medium text-gray-900 mb-4">Ortalama Tamamlanma Süresi</h3>
                    <div class="text-center">
                        <div class="text-3xl font-bold text-blue-600 mb-2">{{ $overviewMetrics['avg_completion_time'] }}</div>
                        <div class="text-sm text-gray-500">saat</div>
                    </div>
                </div>

                <!-- Activity Trend -->
                <div class="bg-white overflow-hidden shadow sm:rounded-lg p-6">
                    <h3 class="text-lg font-medium text-gray-900 mb-4">Son 7 Günlük Aktivite</h3>
                    <div class="flex items-end justify-between h-16 space-x-1">
                        @foreach($overviewMetrics['activity_trend'] as $day)
                            <div class="flex flex-col items-center flex-1">
                                <div class="w-full bg-blue-200 rounded-sm" style="height: {{ $day['count'] > 0 ? max(($day['count'] / max(array_column($overviewMetrics['activity_trend'], 'count'))) * 48, 4) : 2 }}px;"></div>
                                <span class="text-xs text-gray-500 mt-1">{{ $day['date'] }}</span>
                            </div>
                        @endforeach
                    </div>
                </div>
            </div>

            <!-- Status and Priority Distribution -->
            <div class="grid grid-cols-1 lg:grid-cols-2 gap-6 mb-6">
                <!-- Task Status Distribution -->
                <div class="bg-white overflow-hidden shadow sm:rounded-lg p-6">
                    <h3 class="text-lg font-medium text-gray-900 mb-4">Durum Dağılımı</h3>
                    <div class="space-y-3">
                        @foreach($taskStatusDistribution as $status => $count)
                            @php
                                $total = array_sum($taskStatusDistribution);
                                $percentage = $total > 0 ? round(($count / $total) * 100, 1) : 0;
                            @endphp
                            <div class="flex items-center justify-between">
                                <span class="text-sm font-medium text-gray-900">{{ $status }}</span>
                                <div class="flex items-center space-x-2">
                                    <div class="w-24 bg-gray-200 rounded-full h-2">
                                        <div class="bg-blue-600 h-2 rounded-full" style="width: {{ $percentage }}%"></div>
                                    </div>
                                    <span class="text-sm text-gray-500 w-12 text-right">{{ $count }}</span>
                                </div>
                            </div>
                        @endforeach
                    </div>
                </div>

                <!-- Priority Distribution -->
                <div class="bg-white overflow-hidden shadow sm:rounded-lg p-6">
                    <h3 class="text-lg font-medium text-gray-900 mb-4">Öncelik Dağılımı</h3>
                    <div class="space-y-3">
                        @foreach($priorityDistribution as $priority => $data)
                            @php
                                $total = array_sum(array_column($priorityDistribution, 'count'));
                                $percentage = $total > 0 ? round(($data['count'] / $total) * 100, 1) : 0;
                            @endphp
                            <div class="flex items-center justify-between">
                                <span class="text-sm font-medium text-gray-900">{{ $data['label'] }}</span>
                                <div class="flex items-center space-x-2">
                                    <div class="w-24 bg-gray-200 rounded-full h-2">
                                        <div class="h-2 rounded-full" style="width: {{ $percentage }}%; background-color: {{ $data['color'] }}"></div>
                                    </div>
                                    <span class="text-sm text-gray-500 w-12 text-right">{{ $data['count'] }}</span>
                                </div>
                            </div>
                        @endforeach
                    </div>
                </div>
            </div>

            <!-- Top Performers and Recent Activity -->
            <div class="grid grid-cols-1 lg:grid-cols-2 gap-6">
                <!-- Top Performers -->
                <div class="bg-white overflow-hidden shadow sm:rounded-lg p-6">
                    <h3 class="text-lg font-medium text-gray-900 mb-4">En İyi Performans</h3>
                    @if(count($topPerformers) > 0)
                        <div class="space-y-3">
                            @foreach($topPerformers as $performer)
                                <div class="flex items-center justify-between p-3 bg-gray-50 rounded-lg">
                                    <div class="flex items-center space-x-3">
                                        <div class="w-8 h-8 bg-blue-500 rounded-full flex items-center justify-center text-white text-sm font-medium">
                                            {{ substr($performer['user']->name, 0, 1) }}
                                        </div>
                                        <div>
                                            <div class="text-sm font-medium text-gray-900">{{ $performer['user']->name }}</div>
                                            <div class="text-xs text-gray-500">{{ $performer['avg_completion_time'] }}h ortalama</div>
                                        </div>
                                    </div>
                                    <div class="text-right">
                                        <div class="text-sm font-medium text-gray-900">{{ $performer['completed_tasks'] }}</div>
                                        <div class="text-xs text-gray-500">tamamlanan</div>
                                    </div>
                                </div>
                            @endforeach
                        </div>
                    @else
                        <p class="text-gray-500 text-center py-4">Henüz tamamlanan görev bulunmuyor.</p>
                    @endif
                </div>

                <!-- Recent Activity -->
                <div class="bg-white overflow-hidden shadow sm:rounded-lg p-6">
                    <h3 class="text-lg font-medium text-gray-900 mb-4">Son Aktiviteler</h3>
                    @if(count($recentActivity) > 0)
                        <div class="space-y-3 max-h-64 overflow-y-auto">
                            @foreach($recentActivity as $activity)
                                <div class="flex items-start space-x-3">
                                    <div class="w-6 h-6 rounded-full flex items-center justify-center flex-shrink-0 mt-0.5"
                                         style="background-color: {{ $activity['color'] === 'green' ? '#10b981' : '#3b82f6' }}">
                                        <svg class="w-3 h-3 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                            @if($activity['icon'] === 'check-circle')
                                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"></path>
                                            @else
                                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 12h.01M12 12h.01M16 12h.01M21 12c0 4.418-4.03 8-9 8a9.863 9.863 0 01-4.255-.949L3 20l1.395-3.72C3.512 15.042 3 13.574 3 12c0-4.418 4.03-8 9-8s9 3.582 9 8z"></path>
                                            @endif
                                        </svg>
                                    </div>
                                    <div class="flex-1 min-w-0">
                                        <p class="text-sm text-gray-900">{{ $activity['title'] }}</p>
                                        <p class="text-xs text-gray-500">{{ $activity['user'] }} • {{ \Carbon\Carbon::parse($activity['date'])->diffForHumans() }}</p>
                                    </div>
                                </div>
                            @endforeach
                        </div>
                    @else
                        <p class="text-gray-500 text-center py-4">Henüz aktivite bulunmuyor.</p>
                    @endif
                </div>
            </div>
        @endif

        <!-- Time to Completion Analysis -->
        @if($timeToCompletion)
            <div class="bg-white overflow-hidden shadow sm:rounded-lg p-6 mt-6">
                <h3 class="text-lg font-medium text-gray-900 mb-4">Tamamlanma Süresi Analizi</h3>
                <div class="grid grid-cols-2 md:grid-cols-4 gap-4">
                    <div class="text-center">
                        <div class="text-2xl font-bold text-blue-600">{{ $timeToCompletion['average_hours'] }}h</div>
                        <div class="text-sm text-gray-500">Ortalama</div>
                    </div>
                    <div class="text-center">
                        <div class="text-2xl font-bold text-green-600">{{ $timeToCompletion['fastest_completion'] }}h</div>
                        <div class="text-sm text-gray-500">En Hızlı</div>
                    </div>
                    <div class="text-center">
                        <div class="text-2xl font-bold text-red-600">{{ $timeToCompletion['slowest_completion'] }}h</div>
                        <div class="text-sm text-gray-500">En Yavaş</div>
                    </div>
                    <div class="text-center">
                        <div class="text-2xl font-bold text-gray-600">{{ $timeToCompletion['total_completed'] }}</div>
                        <div class="text-sm text-gray-500">Toplam</div>
                    </div>
                </div>
            </div>
        @endif

        <!-- Task Analysis View -->
        @if($selectedMetric === 'tasks' && isset($taskCompletionData))
            <div class="bg-white overflow-hidden shadow sm:rounded-lg p-6">
                <h3 class="text-lg font-medium text-gray-900 mb-6">Görev Tamamlanma Analizi</h3>
                
                <!-- Daily Completions Chart Placeholder -->
                <div class="mb-6">
                    <h4 class="text-md font-medium text-gray-700 mb-3">Günlük Tamamlamalar</h4>
                    <div class="bg-gray-50 p-4 rounded-lg">
                        <p class="text-sm text-gray-600 text-center">Grafik görünümü geliştirilme aşamasında...</p>
                        <div class="mt-2 text-xs text-gray-500">
                            @foreach($taskCompletionData['daily_completions'] as $date => $count)
                                {{ $date }}: {{ $count }} görev tamamlandı<br>
                            @endforeach
                        </div>
                    </div>
                </div>
            </div>
        @endif

        <!-- User Performance View -->
        @if($selectedMetric === 'users' && isset($userPerformanceData))
            <div class="bg-white overflow-hidden shadow sm:rounded-lg p-6">
                <h3 class="text-lg font-medium text-gray-900 mb-6">Kullanıcı Performans Analizi</h3>
                
                @if(count($userPerformanceData) > 0)
                    <div class="overflow-x-auto">
                        <table class="min-w-full divide-y divide-gray-200">
                            <thead class="bg-gray-50">
                                <tr>
                                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Kullanıcı</th>
                                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Toplam Görev</th>
                                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Tamamlanan</th>
                                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Aktif</th>
                                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Tamamlanma Oranı</th>
                                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Ort. Süre</th>
                                </tr>
                            </thead>
                            <tbody class="bg-white divide-y divide-gray-200">
                                @foreach($userPerformanceData as $data)
                                    <tr>
                                        <td class="px-6 py-4 whitespace-nowrap">
                                            <div class="flex items-center">
                                                <div class="w-8 h-8 bg-blue-500 rounded-full flex items-center justify-center text-white text-sm font-medium mr-3">
                                                    {{ substr($data['user']->name, 0, 1) }}
                                                </div>
                                                <div class="text-sm font-medium text-gray-900">{{ $data['user']->name }}</div>
                                            </div>
                                        </td>
                                        <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-900">{{ $data['total_tasks'] }}</td>
                                        <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-900">{{ $data['completed_tasks'] }}</td>
                                        <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-900">{{ $data['active_tasks'] }}</td>
                                        <td class="px-6 py-4 whitespace-nowrap">
                                            <div class="flex items-center">
                                                <div class="w-16 bg-gray-200 rounded-full h-2 mr-2">
                                                    <div class="bg-green-600 h-2 rounded-full" style="width: {{ $data['completion_rate'] }}%"></div>
                                                </div>
                                                <span class="text-sm text-gray-900">{{ $data['completion_rate'] }}%</span>
                                            </div>
                                        </td>
                                        <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-900">{{ $data['avg_completion_time'] }}h</td>
                                    </tr>
                                @endforeach
                            </tbody>
                        </table>
                    </div>
                @else
                    <p class="text-gray-500 text-center py-8">Kullanıcı performans verisi bulunamadı.</p>
                @endif
            </div>
        @endif

        <!-- Productivity Trends View -->
        @if($selectedMetric === 'trends' && isset($productivityTrends))
            <div class="bg-white overflow-hidden shadow sm:rounded-lg p-6">
                <h3 class="text-lg font-medium text-gray-900 mb-6">Verimlilik Trendleri</h3>
                
                <div class="bg-gray-50 p-4 rounded-lg">
                    <p class="text-sm text-gray-600 text-center mb-4">Trend grafiği geliştirilme aşamasında...</p>
                    <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-4 gap-4 text-xs">
                        @foreach(array_slice($productivityTrends, -10) as $trend)
                            <div class="bg-white p-2 rounded border">
                                <div class="font-medium">{{ \Carbon\Carbon::parse($trend['date'])->format('d.m') }}</div>
                                <div class="text-green-600">+{{ $trend['tasks_completed'] }} tamamlandı</div>
                                <div class="text-blue-600">+{{ $trend['tasks_created'] }} oluşturuldu</div>
                                <div class="text-purple-600">{{ $trend['productivity_score'] }} puan</div>
                            </div>
                        @endforeach
                    </div>
                </div>
            </div>
        @endif
    </div>
</div>