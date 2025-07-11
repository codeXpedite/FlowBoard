<!DOCTYPE html>
<html>
<head>
    <meta charset="utf-8">
    <title>Proje Raporu - {{ $project->name }}</title>
    <style>
        body {
            font-family: 'DejaVu Sans', sans-serif;
            font-size: 12px;
            line-height: 1.4;
            color: #333;
        }
        .header {
            text-align: center;
            border-bottom: 2px solid #3b82f6;
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
            background-color: #f3f4f6;
            padding: 8px 12px;
            font-weight: bold;
            font-size: 14px;
            color: #1f2937;
            border-left: 4px solid #3b82f6;
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
            color: #3b82f6;
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
        .priority-high { color: #dc2626; font-weight: bold; }
        .priority-medium { color: #d97706; }
        .priority-low { color: #059669; }
        .recommendation {
            background-color: #fef3cd;
            border: 1px solid #fbbf24;
            padding: 10px;
            margin-bottom: 10px;
            border-radius: 4px;
        }
        .recommendation-title {
            font-weight: bold;
            color: #92400e;
        }
        .recommendation-action {
            font-style: italic;
            color: #1f2937;
            margin-top: 5px;
        }
        .bottleneck {
            background-color: #fee2e2;
            border: 1px solid #f87171;
            padding: 10px;
            margin-bottom: 10px;
            border-radius: 4px;
        }
        .bottleneck-title {
            font-weight: bold;
            color: #dc2626;
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
        <h1>Proje Raporu</h1>
        <p><strong>{{ $project->name }}</strong></p>
        <p>Rapor Tarihi: {{ $reportData['generated_at']->format('d.m.Y H:i') }}</p>
        @if($reportData['report_period']['start_date'])
            <p>Rapor Dönemi: {{ $reportData['report_period']['start_date']->format('d.m.Y') }} - {{ $reportData['report_period']['end_date']->format('d.m.Y') }} ({{ $reportData['report_period']['days'] }} gün)</p>
        @else
            <p>Rapor Dönemi: Tüm Zaman</p>
        @endif
    </div>

    <!-- Executive Summary -->
    <div class="section">
        <div class="section-title">Yönetici Özeti</div>
        
        <div class="metrics-grid">
            <div class="metric-card">
                <div class="metric-value">{{ $reportData['summary_metrics']['completion_rate'] }}%</div>
                <div class="metric-label">Tamamlanma Oranı</div>
            </div>
            <div class="metric-card">
                <div class="metric-value">{{ $reportData['summary_metrics']['velocity'] }}</div>
                <div class="metric-label">Haftalık Hız</div>
            </div>
            <div class="metric-card">
                <div class="metric-value">{{ $reportData['summary_metrics']['overdue_tasks'] }}</div>
                <div class="metric-label">Geciken Görevler</div>
            </div>
            <div class="metric-card">
                <div class="metric-value">{{ $reportData['project_info']['team_size'] }}</div>
                <div class="metric-label">Ekip Üyesi</div>
            </div>
        </div>

        <table class="table">
            <tr>
                <th>Metrik</th>
                <th>Değer</th>
                <th>Açıklama</th>
            </tr>
            <tr>
                <td>Toplam Görev</td>
                <td>{{ $reportData['summary_metrics']['total_tasks'] }}</td>
                <td>Rapor döneminde oluşturulan toplam görev sayısı</td>
            </tr>
            <tr>
                <td>Tamamlanan Görev</td>
                <td>{{ $reportData['summary_metrics']['completed_tasks'] }}</td>
                <td>Başarıyla tamamlanan görev sayısı</td>
            </tr>
            <tr>
                <td>Aktif Görev</td>
                <td>{{ $reportData['summary_metrics']['active_tasks'] }}</td>
                <td>Halen devam eden görev sayısı</td>
            </tr>
            <tr>
                <td>Ortalama Tamamlanma Süresi</td>
                <td>{{ $reportData['summary_metrics']['avg_completion_time'] }} saat</td>
                <td>Görevlerin ortalama tamamlanma süresi</td>
            </tr>
        </table>
    </div>

    <!-- Team Performance -->
    @if(!empty($reportData['team_performance']))
        <div class="section">
            <div class="section-title">Ekip Performansı</div>
            
            <table class="table">
                <thead>
                    <tr>
                        <th>Kullanıcı</th>
                        <th>Toplam</th>
                        <th>Tamamlanan</th>
                        <th>Geciken</th>
                        <th>Oran (%)</th>
                        <th>Ort. Süre (h)</th>
                        <th>Puan</th>
                        <th>Değerlendirme</th>
                    </tr>
                </thead>
                <tbody>
                    @foreach(array_slice($reportData['team_performance'], 0, 10) as $performance)
                        <tr>
                            <td>{{ $performance['user']->name }}</td>
                            <td>{{ $performance['total_tasks'] }}</td>
                            <td>{{ $performance['completed_tasks'] }}</td>
                            <td>{{ $performance['overdue_tasks'] }}</td>
                            <td>{{ $performance['completion_rate'] }}</td>
                            <td>{{ $performance['avg_completion_time'] }}</td>
                            <td>{{ $performance['productivity_score'] }}</td>
                            <td>{{ $performance['rating'] }}</td>
                        </tr>
                    @endforeach
                </tbody>
            </table>
        </div>
    @endif

    <!-- Task Breakdown -->
    @if(!empty($reportData['task_breakdown']))
        <div class="section">
            <div class="section-title">Görev Dağılım Analizi</div>
            
            <div class="two-column">
                <div class="column">
                    <h4>Durum Dağılımı</h4>
                    <table class="table">
                        <thead>
                            <tr>
                                <th>Durum</th>
                                <th>Sayı</th>
                            </tr>
                        </thead>
                        <tbody>
                            @foreach($reportData['task_breakdown']['status_breakdown'] as $status => $count)
                                <tr>
                                    <td>{{ $status }}</td>
                                    <td>{{ $count }}</td>
                                </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>
                
                <div class="column">
                    <h4>Öncelik Dağılımı</h4>
                    <table class="table">
                        <thead>
                            <tr>
                                <th>Öncelik</th>
                                <th>Sayı</th>
                            </tr>
                        </thead>
                        <tbody>
                            @foreach($reportData['task_breakdown']['priority_breakdown'] as $priority => $count)
                                <tr>
                                    <td class="priority-{{ $priority }}">{{ ucfirst($priority) }}</td>
                                    <td>{{ $count }}</td>
                                </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    @endif

    <!-- Progress Timeline -->
    @if(!empty($reportData['progress_timeline']))
        <div class="section">
            <div class="section-title">İlerleme Zaman Çizelgesi</div>
            
            <table class="table">
                <thead>
                    <tr>
                        <th>Hafta</th>
                        <th>Oluşturulan</th>
                        <th>Tamamlanan</th>
                        <th>Net İlerleme</th>
                        <th>Kümülatif</th>
                    </tr>
                </thead>
                <tbody>
                    @foreach(array_slice($reportData['progress_timeline'], -8) as $week)
                        <tr>
                            <td>{{ \Carbon\Carbon::parse($week['week_start'])->format('d.m') }} - {{ \Carbon\Carbon::parse($week['week_end'])->format('d.m') }}</td>
                            <td>{{ $week['tasks_created'] }}</td>
                            <td>{{ $week['tasks_completed'] }}</td>
                            <td style="color: {{ $week['net_progress'] >= 0 ? '#059669' : '#dc2626' }};">
                                {{ $week['net_progress'] > 0 ? '+' : '' }}{{ $week['net_progress'] }}
                            </td>
                            <td><strong>{{ $week['cumulative_completed'] }}</strong></td>
                        </tr>
                    @endforeach
                </tbody>
            </table>
        </div>
    @endif

    <!-- Bottlenecks -->
    @if(!empty($reportData['bottlenecks']))
        <div class="section">
            <div class="section-title">Darboğazlar ve Sorunlar</div>
            
            @foreach($reportData['bottlenecks'] as $bottleneck)
                <div class="bottleneck">
                    <div class="bottleneck-title">{{ $bottleneck['description'] }}</div>
                    <div>Etkilenen Görev: {{ $bottleneck['affected_tasks'] }} | Önem: {{ $bottleneck['severity'] === 'high' ? 'Yüksek' : ($bottleneck['severity'] === 'medium' ? 'Orta' : 'Düşük') }}</div>
                </div>
            @endforeach
        </div>
    @endif

    <!-- Recommendations -->
    @if(!empty($reportData['recommendations']))
        <div class="section">
            <div class="section-title">Öneriler ve Aksiyon Planı</div>
            
            @foreach($reportData['recommendations'] as $rec)
                <div class="recommendation">
                    <div class="recommendation-title">
                        {{ $rec['title'] }} 
                        <span style="font-size: 10px; background: {{ $rec['priority'] === 'high' ? '#dc2626' : ($rec['priority'] === 'medium' ? '#d97706' : '#059669') }}; color: white; padding: 2px 6px; border-radius: 3px;">
                            {{ $rec['priority'] === 'high' ? 'Yüksek' : ($rec['priority'] === 'medium' ? 'Orta' : 'Düşük') }}
                        </span>
                    </div>
                    <div style="margin: 5px 0;">{{ $rec['description'] }}</div>
                    <div class="recommendation-action"><strong>Önerilen Aksiyon:</strong> {{ $rec['action'] }}</div>
                </div>
            @endforeach
        </div>
    @endif

    <!-- Footer -->
    <div class="footer">
        <p>Bu rapor {{ $reportData['generated_at']->format('d.m.Y H:i') }} tarihinde otomatik olarak oluşturulmuştur.</p>
        <p>Proje Yönetim Sistemi - {{ config('app.name') }}</p>
    </div>
</body>
</html>