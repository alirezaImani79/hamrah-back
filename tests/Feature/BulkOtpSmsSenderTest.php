<?php

use App\Services\Sms\BulkOtpSmsSender;
use App\Services\Sms\FakeSmsSender;

it('builds a persian welcome message with the code and a web-otp autofill line', function () {
    $bulk = new FakeSmsSender;

    (new BulkOtpSmsSender($bulk, 'hamrah.test'))->sendCode('+989120000000', '123456');

    $expected = "به همراه خوش آمدید\nکد تأیید شما: 123456\n\n@hamrah.test #123456";

    expect($bulk->messages)->toHaveCount(1)
        ->and($bulk->lastMessageTo('+989120000000'))->toBe($expected);
});

it('omits the web-otp autofill line when no domain is configured', function () {
    $bulk = new FakeSmsSender;

    (new BulkOtpSmsSender($bulk))->sendCode('+989120000000', '123456');

    expect($bulk->lastMessageTo('+989120000000'))->toBe("به همراه خوش آمدید\nکد تأیید شما: 123456");
});
