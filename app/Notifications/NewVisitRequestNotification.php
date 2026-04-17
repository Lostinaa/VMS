<?php

namespace App\Notifications;

use App\Models\VisitRequest;
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
        return ['mail'];
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
}
