<?php

namespace App\Services\Sms;

use App\Contracts\OtpSmsSender;
use App\Contracts\SmsSender;

/**
 * Delivers one-time codes as ordinary SMS through the bulk gateway.
 *
 * This is a temporary adapter. OTP will move to a transactional verify template
 * (see {@see SmsIrVerifySmsSender}) once that template is registered on the
 * sms.ir panel — until then, codes ride on the same bulk SmsSender as every
 * other message.
 */
class BulkOtpSmsSender implements OtpSmsSender
{
    public function __construct(
        private SmsSender $sender,
        private string $domain = '',
    ) {}

    /**
     * Build the OTP message and send it via the bulk gateway.
     *
     * When a domain is configured the message ends with an `@<host> #<code>`
     * line so the browser's Web OTP API can offer to auto-fill the code.
     */
    public function sendCode(string $phoneNumber, string $code): void
    {
        $lines = [
            'به همراه خوش آمدید',
            "کد تأیید شما: {$code}",
        ];

        if ($this->domain !== '') {
            // The Web OTP API only reads the final line, which must match the
            // `@<host> #<code>` form and be preceded by a blank line.
            $lines[] = '';
            $lines[] = "@{$this->domain} #{$code}";
        }

        $this->sender->send($phoneNumber, implode("\n", $lines));
    }
}
