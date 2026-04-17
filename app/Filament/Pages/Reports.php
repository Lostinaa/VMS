<?php

namespace App\Filament\Pages;

use App\Models\VisitRequest;
use App\Exports\VisitReportExport;
use Filament\Pages\Page;
use Filament\Tables\Contracts\HasTable;
use Filament\Tables\Concerns\InteractsWithTable;
use Filament\Tables;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Carbon;
use Barryvdh\DomPDF\Facade\Pdf;
use Maatwebsite\Excel\Facades\Excel;

class Reports extends Page implements HasTable
{
    use InteractsWithTable;

    protected static string|\BackedEnum|null $navigationIcon = 'heroicon-o-chart-bar-square';
    protected static string|\UnitEnum|null $navigationGroup = 'Administration';
    protected static ?int $navigationSort = 10;
    protected string $view = 'filament.pages.reports';

    public static function canAccess(): bool
    {
        return auth()->user()?->role === 'admin';
    }

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

    public function table(Tables\Table $table): Tables\Table
    {
        return $table
            ->query(
                VisitRequest::query()
                    ->with(['visitor', 'host', 'site'])
                    ->whereBetween('scheduled_at', [
                        Carbon::parse($this->dateFrom ?? now()->subDays(30))->startOfDay(),
                        Carbon::parse($this->dateTo ?? now())->endOfDay(),
                    ])
                    ->orderBy('scheduled_at', 'desc')
            )
            ->columns([
                Tables\Columns\TextColumn::make('id')->label('#')->sortable(),
                Tables\Columns\TextColumn::make('visitor.full_name')->searchable()->sortable(),
                Tables\Columns\TextColumn::make('host.name')->searchable()->sortable(),
                Tables\Columns\TextColumn::make('site.name')->sortable(),
                Tables\Columns\TextColumn::make('host.department.name')->label('Department')->sortable(),
                Tables\Columns\TextColumn::make('purpose')->limit(25),
                Tables\Columns\TextColumn::make('visitor_type')->badge()
                    ->color(fn (string $state): string => match ($state) {
                        'external' => 'warning',
                        'internal' => 'info',
                        default => 'gray',
                    }),
                Tables\Columns\TextColumn::make('category')->badge(),
                Tables\Columns\TextColumn::make('status')
                    ->badge()
                    ->color(fn (string $state): string => match ($state) {
                        'pending' => 'warning',
                        'approved' => 'success',
                        'rejected' => 'danger',
                        'checked_in' => 'info',
                        'checked_out' => 'primary',
                        'expired' => 'gray',
                        default => 'gray',
                    }),
                Tables\Columns\TextColumn::make('scheduled_at')->label('Date')->dateTime('M d, H:i')->sortable(),
            ])
            ->filters([
                Tables\Filters\SelectFilter::make('site_id')
                    ->relationship('site', 'name')->label('Site'),
                Tables\Filters\SelectFilter::make('visitor_type')
                    ->options(['external' => 'External', 'internal' => 'Internal']),
                Tables\Filters\SelectFilter::make('category')
                    ->options([
                        'general' => 'General', 'contractor' => 'Contractor',
                        'vendor' => 'Vendor', 'vip' => 'VIP',
                        'job_applicant' => 'Job Applicant', 'other' => 'Other',
                    ]),
            ])
            ->striped()
            ->deferLoading()
            ->emptyStateHeading('No visits found in this period');
    }

    public function getVisitData(): \Illuminate\Support\Collection
    {
        $from = Carbon::parse($this->dateFrom)->startOfDay();
        $to = Carbon::parse($this->dateTo)->endOfDay();

        return VisitRequest::with(['visitor', 'host', 'site', 'zone'])
            ->whereBetween('scheduled_at', [$from, $to])
            ->orderBy('scheduled_at', 'desc')
            ->get();
    }

    public function exportCsv(): \Symfony\Component\HttpFoundation\StreamedResponse
    {
        $data = $this->getVisitData();

        return response()->streamDownload(function () use ($data) {
            $handle = fopen('php://output', 'w');
            fputcsv($handle, [
                '#', 'Reference', 'Visitor', 'Organization', 'Email', 'Phone', 'Car Plate',
                'Host', 'Department', 'Site', 'Zone', 'Meeting Location',
                'Purpose', 'Category', 'Visitor Type', 'Status', 'Scheduled At',
                'Duration (hrs)', 'Parking',
            ]);

            foreach ($data as $row) {
                fputcsv($handle, [
                    $row->id,
                    'VMS-' . str_pad($row->id, 5, '0', STR_PAD_LEFT),
                    $row->visitor->full_name ?? '',
                    $row->visitor->organization ?? '',
                    $row->visitor->email ?? '',
                    $row->visitor->phone ?? '',
                    $row->visitor->car_plate_number ?? '',
                    $row->host->name ?? '',
                    $row->host->department?->name ?? '',
                    $row->site->name ?? '',
                    $row->zone->name ?? '',
                    $row->meeting_location ?? '',
                    $row->purpose,
                    $row->category,
                    $row->visitor_type,
                    $row->status,
                    $row->scheduled_at?->format('Y-m-d H:i'),
                    $row->expected_duration_hours ?? '',
                    $row->parking_number ?? '',
                ]);
            }
            fclose($handle);
        }, 'vms-report-' . now()->format('Y-m-d') . '.csv');
    }

    public function exportExcel()
    {
        return Excel::download(
            new VisitReportExport($this->dateFrom, $this->dateTo),
            'vms-report-' . now()->format('Y-m-d') . '.xlsx'
        );
    }

    public function exportPdf(): \Symfony\Component\HttpFoundation\Response
    {
        $stats = [
            'total_visits' => VisitRequest::whereBetween('scheduled_at', [Carbon::parse($this->dateFrom)->startOfDay(), Carbon::parse($this->dateTo)->endOfDay()])->count(),
            'approved' => VisitRequest::whereBetween('scheduled_at', [Carbon::parse($this->dateFrom)->startOfDay(), Carbon::parse($this->dateTo)->endOfDay()])->where('status', 'approved')->count(),
            'rejected' => VisitRequest::whereBetween('scheduled_at', [Carbon::parse($this->dateFrom)->startOfDay(), Carbon::parse($this->dateTo)->endOfDay()])->where('status', 'rejected')->count(),
            'checked_in' => VisitRequest::whereBetween('scheduled_at', [Carbon::parse($this->dateFrom)->startOfDay(), Carbon::parse($this->dateTo)->endOfDay()])->where('status', 'checked_in')->count(),
            'checked_out' => VisitRequest::whereBetween('scheduled_at', [Carbon::parse($this->dateFrom)->startOfDay(), Carbon::parse($this->dateTo)->endOfDay()])->where('status', 'checked_out')->count(),
            'pending' => VisitRequest::whereBetween('scheduled_at', [Carbon::parse($this->dateFrom)->startOfDay(), Carbon::parse($this->dateTo)->endOfDay()])->where('status', 'pending')->count(),
            'unique_visitors' => VisitRequest::whereBetween('scheduled_at', [Carbon::parse($this->dateFrom)->startOfDay(), Carbon::parse($this->dateTo)->endOfDay()])->distinct('visitor_id')->count('visitor_id'),
            'avg_daily' => round(VisitRequest::whereBetween('scheduled_at', [Carbon::parse($this->dateFrom)->startOfDay(), Carbon::parse($this->dateTo)->endOfDay()])->count() / max(1, Carbon::parse($this->dateFrom)->diffInDays(Carbon::parse($this->dateTo)))),
        ];
        
        $data = $this->getVisitData();
        $dateFrom = $this->dateFrom;
        $dateTo = $this->dateTo;

        $pdf = Pdf::loadView('reports.pdf', compact('stats', 'data', 'dateFrom', 'dateTo'))
            ->setPaper('a4', 'landscape');

        return $pdf->download('vms-report-' . now()->format('Y-m-d') . '.pdf');
    }
}
