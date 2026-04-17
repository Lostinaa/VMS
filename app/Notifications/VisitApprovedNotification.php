<?php

namespace App\Notifications;

use App\Models\VisitRequest;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

class VisitApprovedNotification extends Notification implements ShouldQueue
{
    use Queueable;

    public function __construct(
        public VisitRequest $visitRequest
    ) {}

    public function via(object $notifiable): array
    {
        return ['mail'];
    }

    public function toMail(object $notifiable): MailMessage
    {
        $visit = $this->visitRequest;
        $refCode = 'VMS-' . str_pad($visit->id, 5, '0', STR_PAD_LEFT);

        return (new MailMessage)
            ->subject("Visit Request Approved — {$refCode}")
            ->greeting("Hello {$visit->visitor->full_name},")
            ->line("Your visit request to **{$visit->site->name}** has been approved.")
            ->line("**Reference:** {$refCode}")
            ->line("**Host:** {$visit->host->name}")
            ->line("**Date:** {$visit->scheduled_at->format('M d, Y \\a\\t H:i')}")
            ->line("**Purpose:** {$visit->purpose}")
            ->when($visit->zone, fn ($mail) => $mail->line("**Zone:** {$visit->zone->name}"))
            ->line('Please bring a valid government-issued ID for verification at check-in.')
            ->action('View Visit Details', url("/visit-request"))
            ->line('Thank you for visiting Ethio Telecom.');
    }
}
