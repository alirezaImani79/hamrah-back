<?php

use App\Services\Sms\SmsIrVerifySmsSender;
use Illuminate\Support\Facades\Http;

const SMSIR_VERIFY_ENDPOINT = 'https://api.sms.ir/v1/send/verify';

function smsIrVerifySender(): SmsIrVerifySmsSender
{
    return new SmsIrVerifySmsSender('test-key', 100000, 'Code', SMSIR_VERIFY_ENDPOINT);
}

it('posts the code to the sms.ir verify endpoint using the configured template', function () {
    Http::fake([
        SMSIR_VERIFY_ENDPOINT => Http::response(['status' => 1, 'message' => 'success'], 200),
    ]);

    smsIrVerifySender()->sendCode('09120000000', '123456');

    Http::assertSent(function ($request) {
        return $request->url() === SMSIR_VERIFY_ENDPOINT
            && $request->hasHeader('X-API-KEY', 'test-key')
            && $request['mobile'] === '09120000000'
            && $request['templateId'] === 100000
            && $request['parameters'] === [['name' => 'Code', 'value' => '123456']];
    });
});

it('normalizes an +98 number to the local 0-prefixed form', function () {
    Http::fake([
        SMSIR_VERIFY_ENDPOINT => Http::response(['status' => 1], 200),
    ]);

    smsIrVerifySender()->sendCode('+989120000000', '123456');

    Http::assertSent(fn ($request) => $request['mobile'] === '09120000000');
});

it('throws when sms.ir returns a non-successful status', function () {
    Http::fake([
        SMSIR_VERIFY_ENDPOINT => Http::response(['status' => 0, 'message' => 'Invalid template'], 200),
    ]);

    expect(fn () => smsIrVerifySender()->sendCode('09120000000', '123456'))
        ->toThrow(RuntimeException::class);
});

it('throws when the gateway responds with an http error', function () {
    Http::fake([
        SMSIR_VERIFY_ENDPOINT => Http::response(null, 500),
    ]);

    expect(fn () => smsIrVerifySender()->sendCode('09120000000', '123456'))
        ->toThrow(RuntimeException::class);
});
