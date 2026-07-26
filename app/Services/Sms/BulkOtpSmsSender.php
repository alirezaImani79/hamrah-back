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
    public function __construct(private SmsSender $sender) {}

    /**
     * Format the code as a plain message and send it via the bulk gateway.
     */
    public function sendCode(string $phoneNumber, string $code): void
    {
        $this->sender->send($phoneNumber, "Your verification code is: {$code}");
    }
}
