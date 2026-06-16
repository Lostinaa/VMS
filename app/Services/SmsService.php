<?php

namespace App\Services;

use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Http;

class SmsService
{
    /**
     * Send an SMS notification.
     */
    public function send(string $to, string $message): void
    {
        $enabled = config('services.sms.enabled', false);
        $url = config('services.sms.url', 'https://smsgw.ethiotelecom.et/bl/index.php');

        // Clean/format the phone number (keep only digits)
        $formattedPhone = preg_replace('/[^0-9]/', '', $to);

        Log::info(sprintf('[SMS GATEWAY] Outgoing SMS to %s (formatted: %s): "%s" (Enabled: %s)', $to, $formattedPhone, $message, $enabled ? 'true' : 'false'));

        if (!$enabled) {
            return;
        }

        try {
            $response = Http::withoutVerifying()
                ->timeout(10)
                ->get($url, [
                    'receiver' => $formattedPhone,
                    'message' => $message,
                ]);

            Log::info('SMS gateway response', [
                'status' => $response->status(),
                'body' => $response->body(),
            ]);
        } catch (\Exception $e) {
            Log::error('SMS gateway exception', [
                'message' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
            ]);
        }
    }
}

