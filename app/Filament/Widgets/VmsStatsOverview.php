<?php

namespace App\Filament\Widgets;

use App\Models\CheckIn;
use App\Models\VisitRequest;
use App\Models\Alert;
use App\Models\Visitor;
use Filament\Widgets\StatsOverviewWidget as BaseWidget;
use Filament\Widgets\StatsOverviewWidget\Stat;

class VmsStatsOverview extends BaseWidget
{
    protected static ?int $sort = 1;
    protected ?string $pollingInterval = '10s';

    protected function getStats(): array
    {
        $activeVisitors = CheckIn::whereNull('checked_out_at')->count();
        $todayScheduled = VisitRequest::whereDate('scheduled_at', today())->count();
        $pendingApprovals = VisitRequest::where('status', 'pending')->count();
        $activeAlerts = Alert::whereNull('acknowledged_at')->count();

        return [
            Stat::make('Active Visitors', $activeVisitors)
                ->description('Currently on-site')
                ->descriptionIcon('heroicon-m-user-group')
                ->color('success')
                ->chart([2, 4, 6, 8, $activeVisitors]),

            Stat::make('Today\'s Visits', $todayScheduled)
                ->description('Scheduled for today')
                ->descriptionIcon('heroicon-m-calendar')
                ->color('info')
                ->chart([3, 5, 7, 4, $todayScheduled]),

            Stat::make('Pending Approvals', $pendingApprovals)
                ->description('Awaiting review')
                ->descriptionIcon('heroicon-m-clock')
                ->color('warning')
                ->chart([1, 3, 2, 5, $pendingApprovals]),

            Stat::make('Active Alerts', $activeAlerts)
                ->description('Unacknowledged')
                ->descriptionIcon('heroicon-m-bell-alert')
                ->color($activeAlerts > 0 ? 'danger' : 'success')
                ->chart([0, 1, 2, 1, $activeAlerts]),
        ];
    }
}
