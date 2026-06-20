<?php

use App\Contracts\SmsSender;
use App\Models\OtpCode;
use App\Services\Sms\FakeSmsSender;

beforeEach(function () {
    $this->sms = new FakeSmsSender;
    $this->app->instance(SmsSender::class, $this->sms);
});

it('issues a hashed otp code and dispatches it via sms', function () {
    $response = $this->postJson('/api/v1/auth/otp/request', [
        'phone_number' => '+15551234567',
    ]);

    $response->assertOk()
        ->assertJsonPath('success', true)
        ->assertJsonStructure(['success', 'message', 'data']);

    $otp = OtpCode::where('phone_number', '+15551234567')->sole();

    expect($this->sms->messages)->toHaveCount(1)
        ->and($otp->code)->not->toBe('123456') // stored as a hash, never plain text
        ->and(strlen($otp->code))->toBeGreaterThan(20);
});

it('rejects an invalid phone number', function () {
    $this->postJson('/api/v1/auth/otp/request', ['phone_number' => 'not-a-phone'])
        ->assertStatus(422)
        ->assertJsonPath('success', false)
        ->assertJsonStructure(['success', 'message', 'errors' => ['phone_number']]);
});

it('throttles rapid re-requests for the same number', function () {
    $phone = '+15551234567';

    $this->postJson('/api/v1/auth/otp/request', ['phone_number' => $phone])->assertOk();

    $this->postJson('/api/v1/auth/otp/request', ['phone_number' => $phone])
        ->assertStatus(422)
        ->assertJsonPath('success', false);
});
