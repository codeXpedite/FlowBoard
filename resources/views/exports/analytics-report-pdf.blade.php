<!DOCTYPE html>
<html>
<head>
    <meta charset="utf-8">
    <title>Analitik Raporu - {{ $project->name }}</title>
    <style>
        body {
            font-family: 'DejaVu Sans', sans-serif;
            font-size: 12px;
            line-height: 1.4;
            color: #333;
        }
        .header {
            text-align: center;
            border-bottom: 2px solid #f59e0b;
            padding-bottom: 20px;
            margin-bottom: 30px;
        }
        .header h1 {
            color: #1f2937;
            font-size: 24px;
            margin: 0;
        }
        .header p {
            color: #6b7280;
            margin: 5px 0;
        }
        .section {
            margin-bottom: 25px;
        }
        .section-title {
            background-color: #fef3cd;
            padding: 8px 12px;
            font-weight: bold;
            font-size: 14px;
            color: #1f2937;
            border-left: 4px solid #f59e0b;
            margin-bottom: 15px;
        }
        .metrics-grid {
            display: table;
            width: 100%;
            margin-bottom: 20px;
        }
        .metric-card {
            display: table-cell;
            width: 25%;
            text-align: center;
            padding: 15px;
            border: 1px solid #e5e7eb;
        }
        .metric-value {
            font-size: 20px;
            font-weight: bold;
            color: #f59e0b;
        }
        .metric-label {
            font-size: 10px;
            color: #6b7280;
            margin-top: 5px;
        }
        .table {
            width: 100%;
            border-collapse: collapse;
            margin-bottom: 15px;
        }
        .table th,
        .table td {
            border: 1px solid #e5e7eb;
            padding: 8px;
            text-align: left;
        }
        .table th {
            background-color: #f9fafb;
            font-weight: bold;
            font-size: 11px;
        }
        .table td {
            font-size: 10px;
        }
        .chart-placeholder {
            background-color: #f3f4f6;
            border: 2px dashed #d1d5db;
            padding: 40px;
            text-align: center;
            color: #6b7280;
            margin: 15px 0;
        }
        .two-column {
            display: table;
            width: 100%;
        }
        .column {
            display: table-cell;
            width: 50%;
            padding-right: 10px;
        }
        .progress-bar {
            background-color: #e5e7eb;
            height: 8px;
            border-radius: 4px;
            overflow: hidden;
            margin: 5px 0;
        }
        .progress-fill {
            height: 100%;
            background-color: #3b82f6;
        }
        .footer {
            border-top: 1px solid #e5e7eb;
            padding-top: 15px;
            margin-top: 30px;
            text-align: center;
            font-size: 10px;
            color: #6b7280;
        }
    </style>
</head>
<body>
    <!-- Header -->
    <div class="header">
        <h1>Analitik Raporu</h1>
        <p><strong>{{ $project->name }}</strong></p>
        <p>Rapor Tarihi: {{ now()->format('d.m.Y H:i') }}</p>
    </div>

    <!-- Overview Metrics -->
    @if(isset($analyticsData['overviewMetrics']))
        <div class="section">
            <div class="section-title">Genel Performans Metrikleri</div>
            
            <div class="metrics-grid">
                <div class="metric-card">
                    <div class="metric-value">{{ $analyticsData['overviewMetrics']['total_tasks'] }}</div>
                    <div class="metric-label">Toplam Görevler</div>
                </div>
                <div class="metric-card">
                    <div class="metric-value">{{ $analyticsData['overviewMetrics']['completed_tasks'] }}</div>
                    <div class="metric-label">Tamamlanan</div>
                </div>
                <div class="metric-card">
                    <div class="metric-value">{{ $analyticsData['overviewMetrics']['completion_rate'] }}%</div>
                    <div class="metric-label">Tamamlanma Oranı</div>
                </div>
                <div class="metric-card">
                    <div class="metric-value">{{ $analyticsData['overviewMetrics']['avg_completion_time'] }}h</div>
                    <div class="metric-label">Ort. Tamamlanma</div>
                </div>
            </div>
        </div>
    @endif

    <!-- Task Status Distribution -->
    @if(isset($analyticsData['taskStatusDistribution']))
        <div class="section">
            <div class="section-title">Görev Durum Dağılımı</div>
            
            <table class="table">
                <thead>
                    <tr>
                        <th>Durum</th>
                        <th>Görev Sayısı</th>
                        <th>Yüzde</th>
                        <th>Görselleştirme</th>
                    </tr>
                </thead>
                <tbody>
                    @php
                        $total = array_sum($analyticsData['taskStatusDistribution']);
                    @endphp
                    @foreach($analyticsData['taskStatusDistribution'] as $status => $count)
                        @php
                            $percentage = $total > 0 ? round(($count / $total) * 100, 1) : 0;
                        @endphp
                        <tr>
                            <td><strong>{{ $status }}</strong></td>
                            <td>{{ $count }}</td>
                            <td>{{ $percentage }}%</td>
                            <td>
                                <div class="progress-bar">
                                    <div class="progress-fill" style="width: {{ $percentage }}%;"></div>
                                </div>
                            </td>
                        </tr>
                    @endforeach
                </tbody>
            </table>
        </div>
    @endif

    <!-- Priority Distribution -->
    @if(isset($analyticsData['priorityDistribution']))
        <div class="section">
            <div class="section-title">Öncelik Dağılımı</div>
            
            <table class="table">
                <thead>
                    <tr>
                        <th>Öncelik</th>
                        <th>Görev Sayısı</th>
                        <th>Yüzde</th>
                        <th>Görselleştirme</th>
                    </tr>
                </thead>
                <tbody>
                    @php
                        $total = array_sum(array_column($analyticsData['priorityDistribution'], 'count'));
                    @endphp
                    @foreach($analyticsData['priorityDistribution'] as $priority => $data)
                        @php
                            $percentage = $total > 0 ? round(($data['count'] / $total) * 100, 1) : 0;
                        @endphp
                        <tr>
                            <td><strong>{{ $data['label'] }}</strong></td>
                            <td>{{ $data['count'] }}</td>
                            <td>{{ $percentage }}%</td>
                            <td>
                                <div class="progress-bar">
                                    <div class="progress-fill" style="width: {{ $percentage }}%; background-color: {{ $data['color'] }};"></div>
                                </div>
                            </td>
                        </tr>
                    @endforeach
                </tbody>
            </table>
        </div>
    @endif

    <!-- Top Performers -->
    @if(isset($analyticsData['topPerformers']) && count($analyticsData['topPerformers']) > 0)
        <div class="section">
            <div class="section-title">En İyi Performans Gösteren Ekip Üyeleri</div>
            
            <table class="table">
                <thead>
                    <tr>
                        <th>Kullanıcı</th>
                        <th>Tamamlanan Görev</th>
                        <th>Ortalama Süre (saat)</th>
                        <th>Toplam Süre (saat)</th>
                    </tr>
                </thead>
                <tbody>
                    @foreach($analyticsData['topPerformers'] as $performer)
                        <tr>
                            <td><strong>{{ $performer['user']->name }}</strong></td>
                            <td>{{ $performer['completed_tasks'] }}</td>
                            <td>{{ $performer['avg_completion_time'] }}</td>
                            <td>{{ $performer['total_time'] }}</td>
                        </tr>
                    @endforeach
                </tbody>
            </table>
        </div>
    @endif

    <!-- Time to Completion Analysis -->
    @if(isset($analyticsData['timeToCompletion']) && $analyticsData['timeToCompletion'])
        <div class="section">
            <div class="section-title">Tamamlanma Süre Analizi</div>
            
            <div class="metrics-grid">
                <div class="metric-card">
                    <div class="metric-value">{{ $analyticsData['timeToCompletion']['average_hours'] }}h</div>
                    <div class="metric-label">Ortalama Süre</div>
                </div>
                <div class="metric-card">
                    <div class="metric-value">{{ $analyticsData['timeToCompletion']['fastest_completion'] }}h</div>
                    <div class="metric-label">En Hızlı</div>
                </div>
                <div class="metric-card">
                    <div class="metric-value">{{ $analyticsData['timeToCompletion']['slowest_completion'] }}h</div>
                    <div class="metric-label">En Yavaş</div>
                </div>
                <div class="metric-card">
                    <div class="metric-value">{{ $analyticsData['timeToCompletion']['total_completed'] }}</div>
                    <div class="metric-label">Toplam Tamamlanan</div>
                </div>
            </div>
        </div>
    @endif

    <!-- Recent Activity -->
    @if(isset($analyticsData['recentActivity']) && count($analyticsData['recentActivity']) > 0)
        <div class="section">
            <div class="section-title">Son Aktiviteler</div>
            
            <table class="table">
                <thead>
                    <tr>
                        <th>Aktivite</th>
                        <th>Kullanıcı</th>
                        <th>Tarih</th>
                        <th>Tür</th>
                    </tr>
                </thead>
                <tbody>
                    @foreach(array_slice($analyticsData['recentActivity'], 0, 15) as $activity)
                        <tr>
                            <td>{{ $activity['title'] }}</td>
                            <td>{{ $activity['user'] }}</td>
                            <td>{{ \Carbon\Carbon::parse($activity['date'])->format('d.m.Y H:i') }}</td>
                            <td>
                                <span style="background-color: {{ $activity['color'] === 'green' ? '#10b981' : '#3b82f6' }}; color: white; padding: 2px 6px; border-radius: 3px; font-size: 9px;">
                                    {{ $activity['type'] === 'task_completed' ? 'Görev' : 'Yorum' }}
                                </span>
                            </td>
                        </tr>
                    @endforeach
                </tbody>
            </table>
        </div>
    @endif

    <!-- Charts Placeholder -->
    <div class="section">
        <div class="section-title">Grafiksel Analiz</div>
        <div class="chart-placeholder">
            <p><strong>Grafik Görünümü</strong></p>
            <p>Bu bölümde zaman içindeki trendler, performans grafikleri ve görsel analitikler yer alacaktır.</p>
            <p>İleriki sürümlerde Chart.js entegrasyonu ile dinamik grafikler eklenecektir.</p>
        </div>
    </div>

    <!-- Summary Insights -->
    <div class="section">
        <div class="section-title">Analitik Özetler</div>
        
        <div style="background-color: #f0f9ff; border: 1px solid #0ea5e9; padding: 15px; border-radius: 4px;">
            <h4 style="color: #0c4a6e; margin-top: 0;">Proje Durumu</h4>
            @if(isset($analyticsData['overviewMetrics']))
                <p>Projenizde toplam <strong>{{ $analyticsData['overviewMetrics']['total_tasks'] }}</strong> görev bulunmakta olup, 
                bunların <strong>{{ $analyticsData['overviewMetrics']['completed_tasks'] }}</strong> tanesi tamamlanmıştır.</p>
                
                <p>Mevcut tamamlanma oranınız <strong>%{{ $analyticsData['overviewMetrics']['completion_rate'] }}</strong> olup, 
                görevler ortalama <strong>{{ $analyticsData['overviewMetrics']['avg_completion_time'] }} saat</strong> içerisinde tamamlanmaktadır.</p>
                
                @if($analyticsData['overviewMetrics']['completion_rate'] >= 80)
                    <p style="color: #059669;"><strong>Mükemmel!</strong> Projeniz çok iyi ilerliyor.</p>
                @elseif($analyticsData['overviewMetrics']['completion_rate'] >= 60)
                    <p style="color: #d97706;"><strong>İyi:</strong> Proje genel olarak iyi durumda, bazı iyileştirmeler yapılabilir.</p>
                @else
                    <p style="color: #dc2626;"><strong>Dikkat:</strong> Proje performansını artırmak için aksiyon alınması önerilir.</p>
                @endif
            @endif
        </div>
    </div>

    <!-- Footer -->
    <div class="footer">
        <p>Bu analitik rapor {{ now()->format('d.m.Y H:i') }} tarihinde otomatik olarak oluşturulmuştur.</p>
        <p>Proje Yönetim Sistemi - {{ config('app.name') }}</p>
    </div>
</body>
</html>