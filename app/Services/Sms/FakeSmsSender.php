<?php

namespace App\Services\Sms;

use App\Contracts\SmsSender;

class FakeSmsSender implements SmsSender
{
    /**
     * The messages that have been "sent".
     *
     * @var array<int, array{phone_number: string, message: string}>
     */
    public array $messages = [];

    /**
     * Record the outgoing SMS for later assertions.
     */
    public function send(string $phoneNumber, string $message): void
    {
        $this->messages[] = [
            'phone_number' => $phoneNumber,
            'message' => $message,
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
}
