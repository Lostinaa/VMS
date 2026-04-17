<?php

namespace App\Notifications;

use App\Models\VisitRequest;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

class VisitReminderNotification extends Notification implements ShouldQueue
{
    use Queueable;

    public function __construct(
        public VisitRequest $visitRequest,
        public string $recipientType = 'visitor', // 'visitor' or 'host'
    ) {}

    public function via(object $notifiable): array
    {
        return ['mail'];
    }

    public function toMail(object $notifiable): MailMessage
    {
        $visit = $this->visitRequest;
        $refCode = 'VMS-' . str_pad($visit->id, 5, '0', STR_PAD_LEFT);

        if ($this->recipientType === 'host') {
            return (new MailMessage)
                ->subject("Visit Reminder — {$visit->visitor->full_name} arriving tomorrow")
                ->greeting("Hello {$visit->host->name},")
                ->line("This is a reminder that you have a scheduled visitor tomorrow.")
                ->line("**Visitor:** {$visit->visitor->full_name}")
                ->line("**Organization:** {$visit->visitor->organization}")
                ->line("**Date:** {$visit->scheduled_at->format('M d, Y \\a\\t H:i')}")
                ->line("**Purpose:** {$visit->purpose}")
                ->when($visit->zone, fn ($mail) => $mail->line("**Zone:** {$visit->zone->name}"))
                ->action('View Visit Requests', url('/admin/visit-requests'))
                ->line('Please ensure you are available to receive your visitor.');
        }

        return (new MailMessage)
            ->subject("Visit Reminder — {$refCode}")
            ->greeting("Hello {$visit->visitor->full_name},")
            ->line("This is a reminder about your upcoming visit to **{$visit->site->name}**.")
            ->line("**Reference:** {$refCode}")
            ->line("**Date:** {$visit->scheduled_at->format('M d, Y \\a\\t H:i')}")
            ->line("**Host:** {$visit->host->name}")
            ->when($visit->meeting_location, fn ($mail) => $mail->line("**Location:** {$visit->meeting_location}"))
            ->line('Please bring a valid government-issued ID for verification.')
            ->action('View Details', url('/visit-request'))
            ->line('We look forward to welcoming you.');
    }
}
