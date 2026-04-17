<?php

namespace App\Console\Commands;

use App\Models\VisitRequest;
use App\Notifications\VisitReminderNotification;
use Illuminate\Console\Command;

class SendVisitReminders extends Command
{
    protected $signature = 'vms:send-reminders';
    protected $description = 'Send reminder notifications for visits scheduled tomorrow (FR-007)';

    public function handle(): int
    {
        $tomorrow = now()->addDay();

        $visits = VisitRequest::where('status', 'approved')
            ->whereDate('scheduled_at', $tomorrow->toDateString())
            ->with(['visitor', 'host', 'site', 'zone'])
            ->get();

        $count = 0;

        foreach ($visits as $visit) {
            // Remind visitor
            if ($visit->visitor->email) {
                $visit->visitor->notify(new VisitReminderNotification($visit, 'visitor'));
            }

            // Remind host
            if ($visit->host) {
                $visit->host->notify(new VisitReminderNotification($visit, 'host'));
            }

            $count++;
        }

        $this->info("Sent reminders for {$count} visits scheduled tomorrow.");

        return self::SUCCESS;
    }
}
