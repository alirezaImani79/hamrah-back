<?php

namespace App\Providers;

use App\Contracts\IdentityVerifier;
use App\Contracts\OtpSmsSender;
use App\Contracts\SmsSender;
use App\Services\Identity\OpenAiIdentityVerifier;
use App\Services\Sms\BulkOtpSmsSender;
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
            return match ($this->resolveSmsDriver()) {
                'smsir' => new SmsIrSmsSender(
                    (string) config('services.sms.smsir.key'),
                    (string) config('services.sms.smsir.line_number'),
                    (string) config('services.sms.smsir.endpoint'),
                ),
                default => new LogSmsSender,
            };
        });

        $this->app->singleton(OtpSmsSender::class, function (): OtpSmsSender {
            // OTP codes are currently delivered as ordinary bulk SMS. Once a
            // verify template is registered on the sms.ir panel, swap this for
            // SmsIrVerifySmsSender (built from services.sms.smsir.{otp_template_id,
            // otp_parameter, verify_endpoint}) to send them transactionally.
            return new BulkOtpSmsSender($this->app->make(SmsSender::class));
        });

        $this->app->singleton(IdentityVerifier::class, function (): IdentityVerifier {
            return new OpenAiIdentityVerifier(
                (string) config('identity.model'),
                (string) config('identity.disk'),
            );
        });
    }

    /**
     * Bootstrap any application services.
     */
    public function boot(): void
    {
        //
    }

    /**
     * Determine which SMS driver to use.
     *
     * An explicit `SMS_DRIVER` always wins; otherwise the gateway is only used
     * in production so non-production environments just log outgoing messages.
     */
    private function resolveSmsDriver(): string
    {
        $driver = config('services.sms.driver');

        if (is_string($driver) && $driver !== '') {
            return $driver;
        }

        return $this->app->environment('production') ? 'smsir' : 'log';
    }
}
