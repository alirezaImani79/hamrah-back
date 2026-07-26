<?php

namespace App\Contracts;

interface OtpSmsSender
{
    /**
     * Deliver a one-time code to the given phone number through a pre-approved
     * transactional template (e.g. the sms.ir verify/pattern gateway).
     */
    public function sendCode(string $phoneNumber, string $code): void;
}
