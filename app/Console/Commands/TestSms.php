<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\Http;

class TestSms extends Command
{
    protected $signature = 'vms:test-sms {to} {message} {--url=https://smsgw.ethiotelecom.et/bl/index.php}';
    protected $description = 'Directly test the Ethio Telecom SMS gateway';

    public function handle(): int
    {
        $to = $this->argument('to');
        $message = $this->argument('message');
        $url = $this->option('url');

        $this->info("Preparing to send SMS...");
        $this->line("URL: {$url}");
        $this->line("To: {$to}");
        $this->line("Message: {$message}");

        $formattedPhone = preg_replace('/[^0-9]/', '', $to);
        $this->line("Formatted Phone: {$formattedPhone}");

        try {
            $this->info("Sending GET request to gateway...");
            $response = Http::withoutVerifying()
                ->timeout(10)
                ->get($url, [
                    'receiver' => $formattedPhone,
                    'message' => $message,
                ]);

            $this->info("Response Status: " . $response->status());
            $this->info("Response Body: " . ($response->body() ?: '[Empty Body]'));
            
            if ($response->successful()) {
                $this->info("SMS Request was sent successfully (HTTP 2xx).");
                return self::SUCCESS;
            } else {
                $this->error("SMS Request failed (HTTP Status Code: " . $response->status() . ")");
                return self::FAILURE;
            }
        } catch (\Exception $e) {
            $this->error("An exception occurred while sending SMS: " . $e->getMessage());
            return self::FAILURE;
        }
    }
}
