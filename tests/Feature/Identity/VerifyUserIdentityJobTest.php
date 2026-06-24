<?php

use App\Enums\IdentityVerificationStatus;
use App\Jobs\VerifyUserIdentity;
use App\Models\User;
use App\Services\Identity\FakeIdentityVerifier;
use App\Services\Sms\FakeSmsSender;

it('verifies the user when the probability meets the threshold', function () {
    config(['identity.threshold' => 0.8]);

    $user = User::factory()->identityVerifying()->create();
    $sms = new FakeSmsSender;

    (new VerifyUserIdentity($user))->handle(new FakeIdentityVerifier(0.92, 'All details match.'), $sms);

    $user->refresh();

    expect($user->identity_status)->toBe(IdentityVerificationStatus::Verified)
        ->and($user->identity_verified_at)->not->toBeNull()
        ->and($user->identity_verification_result['probability'])->toBe(0.92);

    expect($sms->lastMessageTo($user->phone_number))->toContain('verified');
});

it('rejects the user when the probability is below the threshold', function () {
    config(['identity.threshold' => 0.8]);

    $user = User::factory()->identityVerifying()->create();
    $sms = new FakeSmsSender;

    (new VerifyUserIdentity($user))->handle(new FakeIdentityVerifier(0.4, 'The selfie does not match the card.'), $sms);

    $user->refresh();

    expect($user->identity_status)->toBe(IdentityVerificationStatus::Rejected)
        ->and($user->identity_verified_at)->toBeNull()
        ->and($user->identity_verification_result['reason'])->toBe('The selfie does not match the card.');

    expect($sms->lastMessageTo($user->phone_number))->toContain('could not verify');
});

it('ignores users that are no longer awaiting verification', function () {
    $user = User::factory()->identityVerified()->create();
    $sms = new FakeSmsSender;

    (new VerifyUserIdentity($user))->handle(new FakeIdentityVerifier(0.1, 'stale'), $sms);

    $user->refresh();

    expect($user->identity_status)->toBe(IdentityVerificationStatus::Verified)
        ->and($sms->messages)->toBeEmpty();
});
