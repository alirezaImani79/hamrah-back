<?php

namespace App\Services\Identity;

use App\Contracts\IdentityVerifier;
use App\Models\User;

/**
 * Deterministic verifier used in tests and local development so the flow can be
 * exercised without calling OpenAI.
 */
class FakeIdentityVerifier implements IdentityVerifier
{
    public function __construct(
        private float $probability = 1.0,
        private string $reason = 'Fake verification result.',
    ) {}

    /**
     * @return array{probability: float, reason: string}
     */
    public function verify(User $user): array
    {
        return [
            'probability' => $this->probability,
            'reason' => $this->reason,
        ];
    }
}
