<?php

namespace App\Notifications;

use App\Models\VisitRequest;
use App\Services\SmsService;
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
        $channels = ['mail'];

        if (!empty($notifiable->phone)) {
            $channels[] = 'sms';
        }

        return $channels;
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

    /**
     * FR-007: Send SMS notification when visitor checks in.
     */
    public function toSms(object $notifiable): void
    {
        $visit = $this->visitRequest;

        $escort = $visit->zone?->escort_required ? ' ESCORT REQUIRED.' : '';

        app(SmsService::class)->send(
            $notifiable->phone,
            "[Ethio Telecom VMS] {$visit->visitor->full_name} has checked in at {$visit->site->name}.{$escort} Please be available to receive your visitor."
        );
    }
}
