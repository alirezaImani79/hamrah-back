<?php

namespace App\Contracts;

interface SmsSender
{
    /**
     * Deliver an SMS message to the given phone number.
     */
    public function send(string $phoneNumber, string $message): void;
}
