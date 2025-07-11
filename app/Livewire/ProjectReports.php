<?php

namespace App\Livewire;

use App\Models\Project;
use App\Services\ReportService;
use Livewire\Component;
use Illuminate\Support\Facades\Gate;

class ProjectReports extends Component
{
    public Project $project;
    public $dateRange = '30';
    public $reportType = 'progress';
    public $showDetailedView = false;
    public $selectedReportData = null;

    protected $queryString = [
        'dateRange' => ['except' => '30'],
        'reportType' => ['except' => 'progress'],
        'showDetailedView' => ['except' => false],
    ];

    public function mount($project)
    {
        $this->project = Project::where('id', $project)
            ->where('owner_id', auth()->id())
            ->firstOrFail();
    }

    public function updatedDateRange()
    {
        $this->selectedReportData = null;
    }

    public function updatedReportType()
    {
        $this->selectedReportData = null;
        $this->showDetailedView = false;
    }

    public function generateReport()
    {
        $reportService = new ReportService($this->project);
        $this->selectedReportData = $reportService->generateProgressReport($this->dateRange);
    }

    public function toggleDetailedView()
    {
        $this->showDetailedView = !$this->showDetailedView;
    }

    public function exportReport($format = 'pdf')
    {
        $exportService = new \App\Services\ExportService($this->project);
        
        try {
            if ($format === 'pdf') {
                return $exportService->exportToPdf($this->dateRange);
            } elseif ($format === 'excel') {
                return $exportService->exportToExcel($this->dateRange);
            }
        } catch (\Exception $e) {
            session()->flash('error', 'Export hatası: ' . $e->getMessage());
        }
    }

    public function getProgressReport()
    {
        if (!$this->selectedReportData) {
            $this->generateReport();
        }
        return $this->selectedReportData;
    }

    public function getReportSummary()
    {
        $report = $this->getProgressReport();
        
        if (!$report) {
            return null;
        }

        $summary = $report['summary_metrics'];
        $recommendations = $report['recommendations'];
        
        return [
            'completion_rate' => $summary['completion_rate'],
            'velocity' => $summary['velocity'],
            'total_tasks' => $summary['total_tasks'],
            'completed_tasks' => $summary['completed_tasks'],
            'overdue_tasks' => $summary['overdue_tasks'],
            'high_priority_recommendations' => array_filter($recommendations, 
                fn($rec) => $rec['priority'] === 'high'),
            'bottlenecks_count' => count($report['bottlenecks']),
            'team_size' => $report['project_info']['team_size'],
        ];
    }

    public function getTimelineData()
    {
        $report = $this->getProgressReport();
        return $report['progress_timeline'] ?? [];
    }

    public function getTopPerformers()
    {
        $report = $this->getProgressReport();
        $teamPerformance = $report['team_performance'] ?? [];
        
        return array_slice($teamPerformance, 0, 5);
    }

    public function getBottlenecks()
    {
        $report = $this->getProgressReport();
        return $report['bottlenecks'] ?? [];
    }

    public function getRecommendations()
    {
        $report = $this->getProgressReport();
        return $report['recommendations'] ?? [];
    }

    public function getMilestones()
    {
        $report = $this->getProgressReport();
        return $report['milestone_progress'] ?? [];
    }

    public function getTaskBreakdown()
    {
        $report = $this->getProgressReport();
        return $report['task_breakdown'] ?? [];
    }

    public function render()
    {
        $reportSummary = $this->getReportSummary();
        $timelineData = $this->getTimelineData();
        $topPerformers = $this->getTopPerformers();
        $bottlenecks = $this->getBottlenecks();
        $recommendations = $this->getRecommendations();
        $milestones = $this->getMilestones();
        $taskBreakdown = $this->getTaskBreakdown();

        return view('livewire.project-reports', compact(
            'reportSummary',
            'timelineData',
            'topPerformers',
            'bottlenecks',
            'recommendations',
            'milestones',
            'taskBreakdown'
        ))->layout('layouts.app');
    }
}