<?php

namespace App\Notifications;

use App\Channels\SmsChannel;
use App\Models\VisitRequest;
use App\Services\SmsService;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

class VisitorCheckedOutNotification extends Notification implements ShouldQueue
{
    use Queueable;

    public function __construct(
        public VisitRequest $visitRequest
    ) {}

    public function via(object $notifiable): array
    {
        $channels = ['mail'];

        if (!empty($notifiable->phone)) {
            $channels[] = SmsChannel::class;
        }

        return $channels;
    }

    public function toMail(object $notifiable): MailMessage
    {
        $visit = $this->visitRequest;

        return (new MailMessage)
            ->subject("Visitor Checked Out — {$visit->visitor->full_name}")
            ->greeting("Hello {$visit->host->name},")
            ->line("Your visitor **{$visit->visitor->full_name}** from **{$visit->visitor->organization}** has checked out.")
            ->line("**Site:** {$visit->site->name}")
            ->line("**Checked out at:** " . now()->format('M d, Y \\a\\t H:i'))
            ->action('View Visit Details', url("/admin/visit-requests/{$visit->id}/edit"))
            ->line('Thank you for using Ethio Telecom VMS.');
    }

    /**
     * FR-007: Send SMS notification when visitor checks out.
     */
    public function toSms(object $notifiable): void
    {
        $visit = $this->visitRequest;

        app(SmsService::class)->send(
            $notifiable->phone,
            "[Ethio Telecom VMS] Visitor {$visit->visitor->full_name} has checked out from {$visit->site->name} at " . now()->format('H:i') . "."
        );
    }
}
