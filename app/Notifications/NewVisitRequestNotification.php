<?php

namespace App\Notifications;

use App\Models\VisitRequest;
use App\Services\SmsService;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

class NewVisitRequestNotification extends Notification implements ShouldQueue
{
    use Queueable;

    public function __construct(
        public VisitRequest $visitRequest
    ) {}

    public function via(object $notifiable): array
    {
        $channels = ['mail'];

        // FR-007: SMS channel when phone number is available
        if (!empty($notifiable->phone)) {
            $channels[] = 'sms';
        }

        return $channels;
    }

    public function toMail(object $notifiable): MailMessage
    {
        $visit = $this->visitRequest;

        return (new MailMessage)
            ->subject("New Visit Request — {$visit->visitor->full_name}")
            ->greeting("Hello {$visit->host->name},")
            ->line("You have a new visit request pending your review.")
            ->line("**Visitor:** {$visit->visitor->full_name}")
            ->line("**Organization:** {$visit->visitor->organization}")
            ->line("**Purpose:** {$visit->purpose}")
            ->line("**Requested Date:** {$visit->scheduled_at->format('M d, Y \\a\\t H:i')}")
            ->line("**Category:** " . ucfirst(str_replace('_', ' ', $visit->category)))
            ->action('Review Request', url("/admin/visit-requests"))
            ->line('Please approve or reject this request at your earliest convenience.');
    }

    /**
     * FR-007: Send SMS notification for new visit request.
     */
    public function toSms(object $notifiable): void
    {
        $visit = $this->visitRequest;
        $ref = 'VMS-' . str_pad($visit->id, 5, '0', STR_PAD_LEFT);

        app(SmsService::class)->send(
            $notifiable->phone,
            "[Ethio Telecom VMS] New visit request {$ref} from {$visit->visitor->full_name} ({$visit->visitor->organization}). Purpose: {$visit->purpose}. Please review."
        );
    }
}
