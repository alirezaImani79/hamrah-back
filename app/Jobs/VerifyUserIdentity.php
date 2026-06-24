<?php

namespace App\Jobs;

use App\Contracts\IdentityVerifier;
use App\Contracts\SmsSender;
use App\Enums\IdentityVerificationStatus;
use App\Models\User;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Queue\Queueable;

class VerifyUserIdentity implements ShouldQueue
{
    use Queueable;

    /**
     * The number of times the job may be attempted.
     */
    public int $tries = 3;

    /**
     * The number of seconds to wait before retrying the job.
     *
     * @var array<int, int>
     */
    public array $backoff = [30, 120, 300];

    /**
     * Create a new job instance.
     */
    public function __construct(public User $user) {}

    /**
     * Run the automated identity check and update the user accordingly.
     */
    public function handle(IdentityVerifier $verifier, SmsSender $smsSender): void
    {
        // Ignore stale jobs for users that are no longer awaiting a review.
        if ($this->user->identity_status !== IdentityVerificationStatus::Verifying) {
            return;
        }

        $result = $verifier->verify($this->user);

        $verified = $result['probability'] >= (float) config('identity.threshold');

        $this->user->forceFill([
            'identity_status' => $verified
                ? IdentityVerificationStatus::Verified
                : IdentityVerificationStatus::Rejected,
            'identity_verified_at' => $verified ? now() : null,
            'identity_verification_result' => $result,
        ])->save();

        $this->notify($smsSender, $verified);
    }

    /**
     * Text the user the outcome of their verification attempt.
     */
    private function notify(SmsSender $smsSender, bool $verified): void
    {
        if ($this->user->phone_number === null) {
            return;
        }

        $message = $verified
            ? 'Your identity has been verified successfully.'
            : 'We could not verify your identity. Please resubmit with clearer photos and correct details.';

        $smsSender->send($this->user->phone_number, $message);
    }
}
