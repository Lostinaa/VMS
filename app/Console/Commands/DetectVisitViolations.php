<?php

namespace App\Console\Commands;

use App\Models\Alert;
use App\Models\CheckIn;
use App\Models\VisitRequest;
use Illuminate\Console\Command;
use Illuminate\Support\Carbon;

class DetectVisitViolations extends Command
{
    protected $signature = 'vms:detect-violations';
    protected $description = 'Detect overstayed visits and expired visit requests, generate alerts (FR-011)';

    public function handle(): int
    {
        $overstayCount = $this->detectOverstays();
        $expiredCount = $this->expireOldRequests();

        $this->info("Detected {$overstayCount} overstays and expired {$expiredCount} visit requests.");

        return self::SUCCESS;
    }

    /**
     * Detect visitors who have been on-site longer than 8 hours
     * without checking out — generate overstay alerts.
     */
    private function detectOverstays(): int
    {
        $overstayThreshold = now()->subHours(8);
        $count = 0;

        $overstayed = CheckIn::whereNull('checked_out_at')
            ->where('checked_in_at', '<', $overstayThreshold)
            ->with(['visitor', 'visitRequest'])
            ->get();

        foreach ($overstayed as $checkIn) {
            // Skip if an overstay alert already exists for this visitor today
            $existing = Alert::where('type', 'overstay')
                ->where('visitor_id', $checkIn->visitor_id)
                ->whereDate('created_at', today())
                ->exists();

            if ($existing) {
                continue;
            }

            $hours = $checkIn->checked_in_at->diffInHours(now());

            Alert::create([
                'type' => 'overstay',
                'severity' => $hours > 12 ? 'high' : 'medium',
                'visitor_id' => $checkIn->visitor_id,
                'visit_request_id' => $checkIn->visit_request_id,
                'message' => "Visitor {$checkIn->visitor->full_name} has been on-site for {$hours} hours without checking out.",
            ]);

            $count++;
        }

        return $count;
    }

    /**
     * Mark approved/pending visit requests as expired
     * if the scheduled date has passed.
     */
    private function expireOldRequests(): int
    {
        return VisitRequest::whereIn('status', ['pending', 'approved'])
            ->where('scheduled_at', '<', now()->subDay())
            ->update(['status' => 'expired']);
    }
}
