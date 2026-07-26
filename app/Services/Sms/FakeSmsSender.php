<?php

namespace App\Services\Sms;

use App\Contracts\OtpSmsSender;
use App\Contracts\SmsSender;

class FakeSmsSender implements OtpSmsSender, SmsSender
{
    /**
     * The regular messages that have been "sent".
     *
     * @var array<int, array{phone_number: string, message: string}>
     */
    public array $messages = [];

    /**
     * The one-time codes that have been "sent".
     *
     * @var array<int, array{phone_number: string, code: string}>
     */
    public array $codes = [];

    /**
     * Record a regular outgoing SMS for later assertions.
     */
    public function send(string $phoneNumber, string $message): void
    {
        $this->messages[] = [
            'phone_number' => $phoneNumber,
            'message' => $message,
        ];
    }

    /**
     * Record an outgoing OTP code for later assertions.
     */
    public function sendCode(string $phoneNumber, string $code): void
    {
        $this->codes[] = [
            'phone_number' => $phoneNumber,
            'code' => $code,
        ];
    }

    /**
     * Get the most recent message sent to the given phone number.
     */
    public function lastMessageTo(string $phoneNumber): ?string
    {
        $matches = array_filter(
            $this->messages,
            fn (array $message): bool => $message['phone_number'] === $phoneNumber,
        );

        $last = end($matches);

        return $last === false ? null : $last['message'];
    }

    /**
     * Get the most recent one-time code sent to the given phone number.
     */
    public function lastCodeTo(string $phoneNumber): ?string
    {
        $matches = array_filter(
            $this->codes,
            fn (array $code): bool => $code['phone_number'] === $phoneNumber,
        );

        $last = end($matches);

        return $last === false ? null : $last['code'];
    }
}
