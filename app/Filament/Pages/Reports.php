<?php

namespace App\Filament\Pages;

use App\Models\VisitRequest;
use Filament\Pages\Page;
use Illuminate\Support\Carbon;
use Barryvdh\DomPDF\Facade\Pdf;

class Reports extends Page
{
    protected static string|\BackedEnum|null $navigationIcon = 'heroicon-o-chart-bar-square';
    protected static string|\UnitEnum|null $navigationGroup = 'Administration';
    protected static ?int $navigationSort = 10;
    protected string $view = 'filament.pages.reports';

    public ?string $dateFrom = null;
    public ?string $dateTo = null;

    public function mount(): void
    {
        $this->dateFrom = now()->subDays(30)->format('Y-m-d');
        $this->dateTo = now()->format('Y-m-d');
    }

    protected function getHeaderWidgets(): array
    {
        return [
            \App\Filament\Widgets\ReportStatsWidget::make([
                'dateFrom' => $this->dateFrom,
                'dateTo' => $this->dateTo,
            ]),
        ];
    }

    public function getHeaderWidgetsColumns(): int|array
    {
        return 4;
    }

    public function getVisitData(): \Illuminate\Support\Collection
    {
        $from = Carbon::parse($this->dateFrom)->startOfDay();
        $to = Carbon::parse($this->dateTo)->endOfDay();

        return VisitRequest::with(['visitor', 'host', 'site'])
            ->whereBetween('scheduled_at', [$from, $to])
            ->orderBy('scheduled_at', 'desc')
            ->get();
    }

    public function exportCsv(): \Symfony\Component\HttpFoundation\StreamedResponse
    {
        $data = $this->getVisitData();

        return response()->streamDownload(function () use ($data) {
            $handle = fopen('php://output', 'w');
            fputcsv($handle, ['#', 'Visitor', 'Organization', 'Host', 'Site', 'Purpose', 'Category', 'Status', 'Scheduled At']);

            foreach ($data as $row) {
                fputcsv($handle, [
                    $row->id,
                    $row->visitor->full_name ?? '',
                    $row->visitor->organization ?? '',
                    $row->host->name ?? '',
                    $row->site->name ?? '',
                    $row->purpose,
                    $row->category,
                    $row->status,
                    $row->scheduled_at?->format('Y-m-d H:i'),
                ]);
            }
            fclose($handle);
        }, 'vms-report-' . now()->format('Y-m-d') . '.csv');
    }

    public function exportPdf(): \Symfony\Component\HttpFoundation\Response
    {
        $from = Carbon::parse($this->dateFrom)->startOfDay();
        $to = Carbon::parse($this->dateTo)->endOfDay();

        $visits = VisitRequest::whereBetween('scheduled_at', [$from, $to]);
        $stats = [
            'total_visits' => (clone $visits)->count(),
            'approved' => (clone $visits)->where('status', 'approved')->count(),
            'rejected' => (clone $visits)->where('status', 'rejected')->count(),
            'checked_in' => (clone $visits)->where('status', 'checked_in')->count(),
            'checked_out' => (clone $visits)->where('status', 'checked_out')->count(),
            'pending' => (clone $visits)->where('status', 'pending')->count(),
            'unique_visitors' => (clone $visits)->distinct('visitor_id')->count('visitor_id'),
            'avg_daily' => round((clone $visits)->count() / max(1, Carbon::parse($this->dateFrom)->diffInDays(Carbon::parse($this->dateTo)))),
        ];
        $data = $this->getVisitData();
        $dateFrom = $this->dateFrom;
        $dateTo = $this->dateTo;

        $pdf = Pdf::loadView('reports.pdf', compact('stats', 'data', 'dateFrom', 'dateTo'))
            ->setPaper('a4', 'landscape');

        return $pdf->download('vms-report-' . now()->format('Y-m-d') . '.pdf');
    }
}
