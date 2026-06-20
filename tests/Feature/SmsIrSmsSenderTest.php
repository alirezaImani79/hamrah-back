<?php

use App\Services\Sms\SmsIrSmsSender;
use Illuminate\Support\Facades\Http;

const SMSIR_ENDPOINT = 'https://api.sms.ir/v1/send/bulk';

function smsIrSender(): SmsIrSmsSender
{
    return new SmsIrSmsSender('test-key', '30007732', SMSIR_ENDPOINT);
}

it('sends a request to the sms.ir bulk endpoint with the api key and payload', function () {
    Http::fake([
        SMSIR_ENDPOINT => Http::response(['status' => 1, 'message' => 'success'], 200),
    ]);

    smsIrSender()->send('09120000000', 'Welcome!');

    Http::assertSent(function ($request) {
        return $request->url() === SMSIR_ENDPOINT
            && $request->hasHeader('X-API-KEY', 'test-key')
            && $request['lineNumber'] === 30007732
            && $request['messageText'] === 'Welcome!'
            && $request['mobiles'] === ['09120000000'];
    });
});

it('normalizes an +98 number to the local 0-prefixed form', function () {
    Http::fake([
        SMSIR_ENDPOINT => Http::response(['status' => 1], 200),
    ]);

    smsIrSender()->send('+989120000000', 'Hi');

    Http::assertSent(fn ($request) => $request['mobiles'] === ['09120000000']);
});

it('throws when sms.ir returns a non-successful status', function () {
    Http::fake([
        SMSIR_ENDPOINT => Http::response(['status' => 0, 'message' => 'Invalid line number'], 200),
    ]);

    expect(fn () => smsIrSender()->send('09120000000', 'Hi'))
        ->toThrow(RuntimeException::class);
});

it('throws when the gateway responds with an http error', function () {
    Http::fake([
        SMSIR_ENDPOINT => Http::response(null, 500),
    ]);

    expect(fn () => smsIrSender()->send('09120000000', 'Hi'))
        ->toThrow(RuntimeException::class);
});
