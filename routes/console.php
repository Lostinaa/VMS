<?php

use Illuminate\Foundation\Inspiring;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Schedule;

Artisan::command('inspire', function () {
    $this->comment(Inspiring::quote());
})->purpose('Display an inspiring quote');

// Detect overstayed visitors and expire old requests every 30 minutes (FR-011)
Schedule::command('vms:detect-violations')->everyThirtyMinutes();

// Send visit reminders for tomorrow's visits at 8 AM daily (FR-007)
Schedule::command('vms:send-reminders')->dailyAt('08:00');

// Send weekly report to admins every Monday at 7 AM (FR-012)
Schedule::command('vms:send-report --period=weekly')->weeklyOn(1, '07:00');
