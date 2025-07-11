<?php

namespace App\Services;

use App\Models\Project;
use App\Services\ReportService;
use Barryvdh\DomPDF\Facade\Pdf;
use Illuminate\Support\Facades\View;
use Carbon\Carbon;

class ExportService
{
    protected Project $project;
    protected ReportService $reportService;

    public function __construct(Project $project)
    {
        $this->project = $project;
        $this->reportService = new ReportService($project);
    }

    public function exportToPdf(string $dateRange = '30'): \Illuminate\Http\Response
    {
        $reportData = $this->reportService->generateProgressReport($dateRange);
        
        // Generate PDF view
        $pdf = Pdf::loadView('exports.project-report-pdf', [
            'project' => $this->project,
            'reportData' => $reportData,
            'dateRange' => $dateRange,
        ]);

        $filename = 'project-report-' . $this->project->id . '-' . now()->format('Y-m-d') . '.pdf';
        
        return $pdf->download($filename);
    }

    public function exportToExcel(string $dateRange = '30'): \Illuminate\Http\Response
    {
        $reportData = $this->reportService->generateProgressReport($dateRange);
        
        // Create CSV content as a simple Excel alternative
        $csvContent = $this->generateCsvContent($reportData);
        
        $filename = 'project-report-' . $this->project->id . '-' . now()->format('Y-m-d') . '.csv';
        
        return response($csvContent)
            ->header('Content-Type', 'text/csv')
            ->header('Content-Disposition', 'attachment; filename="' . $filename . '"');
    }

    public function exportAnalyticsToPdf(array $analyticsData): \Illuminate\Http\Response
    {
        $pdf = Pdf::loadView('exports.analytics-report-pdf', [
            'project' => $this->project,
            'analyticsData' => $analyticsData,
        ]);

        $filename = 'analytics-report-' . $this->project->id . '-' . now()->format('Y-m-d') . '.pdf';
        
        return $pdf->download($filename);
    }

    private function generateCsvContent(array $reportData): string
    {
        $csv = [];
        
        // Header
        $csv[] = "Proje Raporu - " . $this->project->name;
        $csv[] = "Oluşturulma Tarihi: " . now()->format('d.m.Y H:i');
        $csv[] = "";
        
        // Project Info
        $csv[] = "PROJE BİLGİLERİ";
        $csv[] = "Proje Adı," . $this->project->name;
        $csv[] = "Proje Sahibi," . $reportData['project_info']['owner'];
        $csv[] = "Toplam Görev," . $reportData['project_info']['total_tasks'];
        $csv[] = "Ekip Üyesi," . $reportData['project_info']['team_size'];
        $csv[] = "";
        
        // Summary Metrics
        $summary = $reportData['summary_metrics'];
        $csv[] = "ÖZET METRİKLER";
        $csv[] = "Toplam Görev," . $summary['total_tasks'];
        $csv[] = "Tamamlanan Görev," . $summary['completed_tasks'];
        $csv[] = "Aktif Görev," . $summary['active_tasks'];
        $csv[] = "Geciken Görev," . $summary['overdue_tasks'];
        $csv[] = "Tamamlanma Oranı (%)," . $summary['completion_rate'];
        $csv[] = "Haftalık Hız," . $summary['velocity'];
        $csv[] = "Ortalama Tamamlanma Süresi (saat)," . $summary['avg_completion_time'];
        $csv[] = "";
        
        // Team Performance
        if (!empty($reportData['team_performance'])) {
            $csv[] = "EKİP PERFORMANSI";
            $csv[] = "Kullanıcı,Toplam Görev,Tamamlanan,Geciken,Tamamlanma Oranı (%),Ort. Süre (saat),Puan,Değerlendirme";
            
            foreach ($reportData['team_performance'] as $performance) {
                $csv[] = implode(',', [
                    $performance['user']->name,
                    $performance['total_tasks'],
                    $performance['completed_tasks'],
                    $performance['overdue_tasks'],
                    $performance['completion_rate'],
                    $performance['avg_completion_time'],
                    $performance['productivity_score'],
                    $performance['rating'],
                ]);
            }
            $csv[] = "";
        }
        
        // Task Breakdown
        if (!empty($reportData['task_breakdown'])) {
            $breakdown = $reportData['task_breakdown'];
            
            $csv[] = "GÖREV DAĞILIMI";
            $csv[] = "Durum Dağılımı";
            foreach ($breakdown['status_breakdown'] as $status => $count) {
                $csv[] = $status . "," . $count;
            }
            $csv[] = "";
            
            $csv[] = "Öncelik Dağılımı";
            foreach ($breakdown['priority_breakdown'] as $priority => $count) {
                $csv[] = ucfirst($priority) . "," . $count;
            }
            $csv[] = "";
        }
        
        // Bottlenecks
        if (!empty($reportData['bottlenecks'])) {
            $csv[] = "DARBOĞAZLAR";
            $csv[] = "Açıklama,Etkilenen Görev,Önem Seviyesi";
            
            foreach ($reportData['bottlenecks'] as $bottleneck) {
                $csv[] = implode(',', [
                    '"' . $bottleneck['description'] . '"',
                    $bottleneck['affected_tasks'],
                    $bottleneck['severity'],
                ]);
            }
            $csv[] = "";
        }
        
        // Recommendations
        if (!empty($reportData['recommendations'])) {
            $csv[] = "ÖNERİLER";
            $csv[] = "Başlık,Açıklama,Öncelik,Önerilen Aksiyon";
            
            foreach ($reportData['recommendations'] as $rec) {
                $csv[] = implode(',', [
                    '"' . $rec['title'] . '"',
                    '"' . $rec['description'] . '"',
                    $rec['priority'],
                    '"' . $rec['action'] . '"',
                ]);
            }
        }
        
        return implode("\n", $csv);
    }

    public function generateQuickSummary(string $dateRange = '30'): array
    {
        $reportData = $this->reportService->generateProgressReport($dateRange);
        
        return [
            'project_name' => $this->project->name,
            'completion_rate' => $reportData['summary_metrics']['completion_rate'],
            'total_tasks' => $reportData['summary_metrics']['total_tasks'],
            'completed_tasks' => $reportData['summary_metrics']['completed_tasks'],
            'overdue_tasks' => $reportData['summary_metrics']['overdue_tasks'],
            'velocity' => $reportData['summary_metrics']['velocity'],
            'team_size' => $reportData['project_info']['team_size'],
            'high_priority_issues' => count(array_filter($reportData['recommendations'], 
                fn($rec) => $rec['priority'] === 'high')),
            'bottlenecks_count' => count($reportData['bottlenecks']),
            'generated_at' => now()->format('d.m.Y H:i'),
        ];
    }
}