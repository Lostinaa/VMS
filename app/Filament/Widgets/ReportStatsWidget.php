<?php

namespace App\Filament\Widgets;

use App\Models\VisitRequest;
use Filament\Widgets\StatsOverviewWidget as BaseWidget;
use Filament\Widgets\StatsOverviewWidget\Stat;
use Illuminate\Support\Carbon;

class ReportStatsWidget extends BaseWidget
{
    public ?string $dateFrom = null;
    public ?string $dateTo = null;

    protected static bool $isDiscovered = false;

    protected function getStats(): array
    {
        $from = Carbon::parse($this->dateFrom ?? now()->subDays(30))->startOfDay();
        $to = Carbon::parse($this->dateTo ?? now())->endOfDay();

        $visits = VisitRequest::whereBetween('scheduled_at', [$from, $to]);

        $total = (clone $visits)->count();
        $days = max(1, $from->diffInDays($to));

        return [
            Stat::make('Total Visits', $total)
                ->description('In selected period')
                ->descriptionIcon('heroicon-m-calendar-days')
                ->color('primary'),

            Stat::make('Unique Visitors', (clone $visits)->distinct('visitor_id')->count('visitor_id'))
                ->description('Individual visitors')
                ->descriptionIcon('heroicon-m-user-group')
                ->color('success'),

            Stat::make('Avg Per Day', round($total / $days))
                ->description("{$days} days in range")
                ->descriptionIcon('heroicon-m-chart-bar')
                ->color('info'),

            Stat::make('Pending', (clone $visits)->where('status', 'pending')->count())
                ->description('Awaiting approval')
                ->descriptionIcon('heroicon-m-clock')
                ->color('warning'),

            Stat::make('Approved', (clone $visits)->where('status', 'approved')->count())
                ->description('Ready for check-in')
                ->descriptionIcon('heroicon-m-check-circle')
                ->color('success'),

            Stat::make('Checked In', (clone $visits)->where('status', 'checked_in')->count())
                ->description('Currently on-site')
                ->descriptionIcon('heroicon-m-arrow-right-end-on-rectangle')
                ->color('info'),

            Stat::make('Checked Out', (clone $visits)->where('status', 'checked_out')->count())
                ->description('Completed visits')
                ->descriptionIcon('heroicon-m-arrow-left-start-on-rectangle')
                ->color('primary'),

            Stat::make('Rejected', (clone $visits)->where('status', 'rejected')->count())
                ->description('Denied access')
                ->descriptionIcon('heroicon-m-x-circle')
                ->color('danger'),
        ];
    }
}
