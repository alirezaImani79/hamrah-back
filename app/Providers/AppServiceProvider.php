<?php

namespace App\Providers;

use App\Contracts\SmsSender;
use App\Services\Sms\LogSmsSender;
use App\Services\Sms\SmsIrSmsSender;
use Illuminate\Support\ServiceProvider;

class AppServiceProvider extends ServiceProvider
{
    /**
     * Register any application services.
     */
    public function register(): void
    {
        $this->app->singleton(SmsSender::class, function (): SmsSender {
            return match (config('services.sms.driver')) {
                'smsir' => new SmsIrSmsSender(
                    (string) config('services.sms.smsir.key'),
                    (string) config('services.sms.smsir.line_number'),
                    (string) config('services.sms.smsir.endpoint'),
                ),
                default => new LogSmsSender,
            };
        });
    }

    /**
     * Bootstrap any application services.
     */
    public function boot(): void
    {
        //
    }
}
