<?php

namespace App\Notifications;

use App\Models\VisitRequest;
use App\Services\SmsService;
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
        $channels = ['mail'];

        if (!empty($notifiable->phone)) {
            $channels[] = 'sms';
        }

        return $channels;
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

    /**
     * FR-007: Send SMS notification for approved visit.
     */
    public function toSms(object $notifiable): void
    {
        $visit = $this->visitRequest;
        $ref = 'VMS-' . str_pad($visit->id, 5, '0', STR_PAD_LEFT);

        app(SmsService::class)->send(
            $notifiable->phone,
            "[Ethio Telecom VMS] Your visit {$ref} to {$visit->site->name} on {$visit->scheduled_at->format('M d, Y H:i')} has been APPROVED. Host: {$visit->host->name}. Please bring a valid ID."
        );
    }
}
