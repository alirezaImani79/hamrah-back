<?php

namespace App\Services\Sms;

use App\Contracts\OtpSmsSender;
use App\Contracts\SmsSender;
use Illuminate\Support\Facades\Log;

class LogSmsSender implements OtpSmsSender, SmsSender
{
    /**
     * Write a regular outgoing SMS to the log instead of a real gateway.
     */
    public function send(string $phoneNumber, string $message): void
    {
        Log::info('SMS dispatched', [
            'phone_number' => $phoneNumber,
            'message' => $message,
        ]);
    }

    /**
     * Write an outgoing OTP code to the log instead of a real gateway.
     */
    public function sendCode(string $phoneNumber, string $code): void
    {
        Log::info('OTP SMS dispatched', [
            'phone_number' => $phoneNumber,
            'code' => $code,
        ]);
    }
}
