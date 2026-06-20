<?php

namespace App\Services\Sms;

use App\Contracts\SmsSender;
use Illuminate\Support\Facades\Log;

class LogSmsSender implements SmsSender
{
    /**
     * Write the outgoing SMS to the log instead of a real gateway.
     */
    public function send(string $phoneNumber, string $message): void
    {
        Log::info('SMS dispatched', [
            'phone_number' => $phoneNumber,
            'message' => $message,
        ]);
    }
}
