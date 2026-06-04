<?php

namespace App\Services;

use Illuminate\Support\Facades\Log;

class SmsService
{
    /**
     * Send an SMS notification (logged to laravel.log).
     */
    public function send(string $to, string $message): void
    {
        Log::info(sprintf('[SMS GATEWAY] Outgoing SMS to %s: "%s"', $to, $message));
    }
}
