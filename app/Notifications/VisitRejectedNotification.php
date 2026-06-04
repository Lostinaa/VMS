<?php

namespace App\Notifications;

use App\Models\VisitRequest;
use App\Services\SmsService;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

class VisitRejectedNotification extends Notification implements ShouldQueue
{
    use Queueable;

    public function __construct(
        public VisitRequest $visitRequest,
        public ?string $reason = null,
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

        $mail = (new MailMessage)
            ->subject("Visit Request Update — {$refCode}")
            ->greeting("Hello {$visit->visitor->full_name},")
            ->line("We regret to inform you that your visit request to **{$visit->site->name}** could not be approved at this time.")
            ->line("**Reference:** {$refCode}")
            ->line("**Requested Date:** {$visit->scheduled_at->format('M d, Y \\a\\t H:i')}");

        if ($this->reason) {
            $mail->line("**Reason:** {$this->reason}");
        }

        return $mail
            ->line('You may submit a new request or contact the front desk for assistance.')
            ->action('Submit New Request', url("/visit-request"))
            ->line('Thank you for your understanding.');
    }

    /**
     * FR-007: Send SMS notification for rejected visit.
     */
    public function toSms(object $notifiable): void
    {
        $visit = $this->visitRequest;
        $ref = 'VMS-' . str_pad($visit->id, 5, '0', STR_PAD_LEFT);

        app(SmsService::class)->send(
            $notifiable->phone,
            "[Ethio Telecom VMS] Your visit request {$ref} could not be approved. Please contact the front desk for details."
        );
    }
}
