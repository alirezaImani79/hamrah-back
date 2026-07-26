<?php

use App\Services\Sms\BulkOtpSmsSender;
use App\Services\Sms\FakeSmsSender;

it('formats the otp code as an ordinary message and delegates to the bulk sender', function () {
    $bulk = new FakeSmsSender;

    (new BulkOtpSmsSender($bulk))->sendCode('+989120000000', '123456');

    expect($bulk->messages)->toHaveCount(1)
        ->and($bulk->lastMessageTo('+989120000000'))->toBe('Your verification code is: 123456');
});
