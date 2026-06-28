<?php

use App\Contracts\SmsSender;
use App\Services\Sms\LogSmsSender;
use App\Services\Sms\SmsIrSmsSender;

function resolveSmsSender(): SmsSender
{
    app()->forgetInstance(SmsSender::class);

    return app(SmsSender::class);
}

it('uses the sms.ir gateway in production when no driver is configured', function () {
    config(['services.sms.driver' => null]);
    app()['env'] = 'production';

    expect(resolveSmsSender())->toBeInstanceOf(SmsIrSmsSender::class);
});

it('logs messages outside production when no driver is configured', function () {
    config(['services.sms.driver' => null]);
    app()['env'] = 'local';

    expect(resolveSmsSender())->toBeInstanceOf(LogSmsSender::class);
});

it('honors an explicit sms driver override regardless of environment', function () {
    config(['services.sms.driver' => 'log']);
    app()['env'] = 'production';

    expect(resolveSmsSender())->toBeInstanceOf(LogSmsSender::class);
});
