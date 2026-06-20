<?php

use App\Models\OtpCode;
use App\Models\User;

it('verifies a valid code, creates the user, and returns a token', function () {
    $phone = '+15551234567';
    OtpCode::factory()->forCode('123456')->create(['phone_number' => $phone]);

    $response = $this->postJson('/api/v1/auth/otp/verify', [
        'phone_number' => $phone,
        'code' => '123456',
    ]);

    $response->assertOk()
        ->assertJsonPath('success', true)
        ->assertJsonPath('data.token_type', 'Bearer')
        ->assertJsonStructure(['data' => ['token', 'token_type', 'user' => ['id', 'phone_number']]]);

    $user = User::where('phone_number', $phone)->sole();

    expect($user->phone_verified_at)->not->toBeNull()
        ->and($user->tokens()->count())->toBe(1);
});

it('rejects a wrong code', function () {
    $phone = '+15551234567';
    OtpCode::factory()->forCode('123456')->create(['phone_number' => $phone]);

    $this->postJson('/api/v1/auth/otp/verify', ['phone_number' => $phone, 'code' => '000000'])
        ->assertStatus(422)
        ->assertJsonPath('success', false);

    expect(OtpCode::where('phone_number', $phone)->sole()->attempts)->toBe(1);
});

it('rejects an expired code', function () {
    $phone = '+15551234567';
    OtpCode::factory()->forCode('123456')->expired()->create(['phone_number' => $phone]);

    $this->postJson('/api/v1/auth/otp/verify', ['phone_number' => $phone, 'code' => '123456'])
        ->assertStatus(422);
});

it('rejects an already consumed code', function () {
    $phone = '+15551234567';
    OtpCode::factory()->forCode('123456')->consumed()->create(['phone_number' => $phone]);

    $this->postJson('/api/v1/auth/otp/verify', ['phone_number' => $phone, 'code' => '123456'])
        ->assertStatus(422);
});

it('locks out a code after the maximum attempts', function () {
    config(['otp.max_attempts' => 3]);
    $phone = '+15551234567';
    OtpCode::factory()->forCode('123456')->create(['phone_number' => $phone, 'attempts' => 3]);

    $this->postJson('/api/v1/auth/otp/verify', ['phone_number' => $phone, 'code' => '123456'])
        ->assertStatus(422);
});
