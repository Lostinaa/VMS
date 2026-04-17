<?php

namespace App\Console\Commands;

use App\Models\User;
use App\Models\VisitRequest;
use Illuminate\Console\Command;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Mail;
use Barryvdh\DomPDF\Facade\Pdf;

class SendScheduledReport extends Command
{
    protected $signature = 'vms:send-report {--period=weekly : Report period (daily, weekly, monthly)}';
    protected $description = 'Generate and email a visit report to admins (FR-012)';

    public function handle(): int
    {
        $period = $this->option('period');

        [$from, $to] = match ($period) {
            'daily' => [now()->subDay()->startOfDay(), now()->subDay()->endOfDay()],
            'weekly' => [now()->subWeek()->startOfDay(), now()->subDay()->endOfDay()],
            'monthly' => [now()->subMonth()->startOfDay(), now()->subDay()->endOfDay()],
            default => [now()->subWeek()->startOfDay(), now()->subDay()->endOfDay()],
        };

        $stats = [
            'total_visits' => VisitRequest::whereBetween('scheduled_at', [$from, $to])->count(),
            'approved' => VisitRequest::whereBetween('scheduled_at', [$from, $to])->where('status', 'approved')->count(),
            'rejected' => VisitRequest::whereBetween('scheduled_at', [$from, $to])->where('status', 'rejected')->count(),
            'checked_in' => VisitRequest::whereBetween('scheduled_at', [$from, $to])->where('status', 'checked_in')->count(),
            'checked_out' => VisitRequest::whereBetween('scheduled_at', [$from, $to])->where('status', 'checked_out')->count(),
            'pending' => VisitRequest::whereBetween('scheduled_at', [$from, $to])->where('status', 'pending')->count(),
            'unique_visitors' => VisitRequest::whereBetween('scheduled_at', [$from, $to])->distinct('visitor_id')->count('visitor_id'),
            'avg_daily' => round(VisitRequest::whereBetween('scheduled_at', [$from, $to])->count() / max(1, $from->diffInDays($to))),
        ];

        $data = VisitRequest::with(['visitor', 'host', 'site', 'zone'])
            ->whereBetween('scheduled_at', [$from, $to])
            ->orderBy('scheduled_at', 'desc')
            ->get();

        $dateFrom = $from->format('Y-m-d');
        $dateTo = $to->format('Y-m-d');

        $pdf = Pdf::loadView('reports.pdf', compact('stats', 'data', 'dateFrom', 'dateTo'))
            ->setPaper('a4', 'landscape');

        $filename = "vms-{$period}-report-{$dateTo}.pdf";
        $pdfPath = storage_path("app/reports/{$filename}");

        if (!is_dir(storage_path('app/reports'))) {
            mkdir(storage_path('app/reports'), 0755, true);
        }
        $pdf->save($pdfPath);

        // Send to all admins
        $admins = User::where('role', 'admin')->where('is_active', true)->get();
        $periodLabel = ucfirst($period);

        foreach ($admins as $admin) {
            Mail::raw(
                "Dear {$admin->name},\n\nPlease find attached the {$periodLabel} VMS Report for {$dateFrom} to {$dateTo}.\n\nTotal Visits: {$stats['total_visits']}\nApproved: {$stats['approved']}\nChecked In: {$stats['checked_in']}\nChecked Out: {$stats['checked_out']}\nAvg Daily: {$stats['avg_daily']}\n\nThis is an automated report from the Visitor Management System.\n\n— Ethio Telecom VMS",
                function ($message) use ($admin, $pdfPath, $filename, $periodLabel, $dateTo) {
                    $message->to($admin->email)
                        ->subject("VMS {$periodLabel} Report — {$dateTo}")
                        ->attach($pdfPath, ['as' => $filename, 'mime' => 'application/pdf']);
                }
            );
        }

        // Clean up
        if (file_exists($pdfPath)) {
            unlink($pdfPath);
        }

        $this->info("Sent {$periodLabel} report ({$stats['total_visits']} visits) to {$admins->count()} admin(s).");

        return self::SUCCESS;
    }
}
