<?php

namespace App\Filament\Widgets;

use App\Models\VisitRequest;
use Filament\Widgets\ChartWidget;
use Illuminate\Support\Carbon;

class VisitorTrendsChart extends ChartWidget
{
    protected ?string $heading = 'Visitor Trends (Last 7 Days)';
    protected static ?int $sort = 2;

    protected function getData(): array
    {
        $days = collect(range(6, 0))->map(fn ($i) => Carbon::today()->subDays($i));

        $external = $days->map(fn ($day) =>
            VisitRequest::where('visitor_type', 'external')
                ->whereDate('scheduled_at', $day)->count()
        )->toArray();

        $internal = $days->map(fn ($day) =>
            VisitRequest::where('visitor_type', 'internal')
                ->whereDate('scheduled_at', $day)->count()
        )->toArray();

        return [
            'datasets' => [
                [
                    'label' => 'External Visitors',
                    'data' => $external,
                    'borderColor' => '#3B82F6',
                    'backgroundColor' => 'rgba(59, 130, 246, 0.1)',
                    'fill' => true,
                ],
                [
                    'label' => 'Internal Visitors',
                    'data' => $internal,
                    'borderColor' => '#10B981',
                    'backgroundColor' => 'rgba(16, 185, 129, 0.1)',
                    'fill' => true,
                ],
            ],
            'labels' => $days->map(fn ($d) => $d->format('M d'))->toArray(),
        ];
    }

    protected function getType(): string
    {
        return 'line';
    }
}
