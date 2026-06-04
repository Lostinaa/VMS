<?php

namespace App\Providers;

use Illuminate\Support\ServiceProvider;

class AppServiceProvider extends ServiceProvider
{
    /**
     * Register any application services.
     */
    public function register(): void
    {
        //
    }

    /**
     * Bootstrap any application services.
     */
    public function boot(): void
    {
        \Illuminate\Support\Facades\Notification::extend('sms', function ($app) {
            return new class {
                public function send($notifiable, \Illuminate\Notifications\Notification $notification)
                {
                    if (method_exists($notification, 'toSms')) {
                        $notification->toSms($notifiable);
                    }
                }
            };
        });
    }
}
