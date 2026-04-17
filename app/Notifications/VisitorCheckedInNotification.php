<?php

namespace App\Notifications;

use App\Models\VisitRequest;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

class VisitorCheckedInNotification extends Notification implements ShouldQueue
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

        return (new MailMessage)
            ->subject("Visitor Arrived — {$visit->visitor->full_name}")
            ->greeting("Hello {$visit->host->name},")
            ->line("Your visitor **{$visit->visitor->full_name}** has checked in.")
            ->line("**Organization:** {$visit->visitor->organization}")
            ->line("**Purpose:** {$visit->purpose}")
            ->line("**Site:** {$visit->site->name}")
            ->when($visit->zone, fn ($mail) => $mail->line("**Zone:** {$visit->zone->name}"))
            ->line("**Checked in at:** " . now()->format('M d, Y \\a\\t H:i'))
            ->when(
                $visit->zone?->escort_required,
                fn ($mail) => $mail->line('⚠️ **Escort is required** for this zone. Please meet your visitor at the reception.')
            )
            ->action('View Visit Details', url("/admin/visit-requests/{$visit->id}/edit"))
            ->line('Please be available to receive your visitor.');
    }
}
